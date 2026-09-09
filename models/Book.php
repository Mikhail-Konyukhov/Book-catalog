<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $title
 * @property int $year
 * @property string|null $description
 * @property string $isbn
 * @property string|null $cover_path
 * @property int $created_at
 * @property array $authorIds
 * @property-read Author[] $authors
 */
class Book extends ActiveRecord
{
    /** Каталог обложек относительно web/. */
    public const COVER_DIR = 'uploads';

    /** @var UploadedFile|null */
    public $coverFile;

    private ?array $_authorIds = null;
    private ?string $_replacedCoverPath = null;
    private ?string $_uploadedCoverPath = null;

    public static function tableName(): string
    {
        return '{{%book}}';
    }

    public function behaviors(): array
    {
        return [
            // Колонки updated_at нет: без этой опции поведение будет писать в несуществующее поле.
            ['class' => TimestampBehavior::class, 'updatedAtAttribute' => false],
        ];
    }

    public function transactions(): array
    {
        return [self::SCENARIO_DEFAULT => self::OP_INSERT | self::OP_UPDATE];
    }

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            ['title', 'string', 'max' => 255],
            // Верхняя граница обязательна: иначе значение не влезает в SMALLINT UNSIGNED
            // и MySQL в strict mode отдаёт 500 вместо ошибки формы.
            ['year', 'integer', 'min' => 1450, 'max' => (int) date('Y') + 1],
            ['isbn', 'string', 'max' => 20],
            ['isbn', 'unique'],
            ['description', 'string'],
            ['description', 'default', 'value' => null],
            // Без exist подделанный POST доводит несуществующий id до link() и роняет запрос на FK.
            [
                'authorIds',
                'exist',
                'targetClass' => Author::class,
                // ExistValidator с allowArray требует targetAttribute строкой,
                // форма ['authorIds' => 'id'] из контракта даёт InvalidConfigException.
                'targetAttribute' => 'id',
                'allowArray' => true,
            ],
            [
                'coverFile',
                'file',
                'skipOnEmpty' => true,
                'extensions' => 'png, jpg, jpeg',
                'mimeTypes' => 'image/png, image/jpeg',
                'maxSize' => 2 * 1024 * 1024,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'year' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'cover_path' => 'Обложка',
            'coverFile' => 'Обложка',
            'authorIds' => 'Авторы',
            'created_at' => 'Добавлена',
        ];
    }

    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('{{%book_author}}', ['book_id' => 'id']);
    }

    /**
     * @return int[]
     */
    public function getAuthorIds(): array
    {
        if ($this->_authorIds === null) {
            // ponytail: геттер вместо afterFind — связь читается только когда форме нужны id,
            // а не на каждой строке списка.
            $this->_authorIds = $this->isNewRecord
                ? []
                : array_map('intval', ArrayHelper::getColumn($this->authors, 'id'));
        }

        return $this->_authorIds;
    }

    public function setAuthorIds(mixed $value): void
    {
        $this->_authorIds = array_values(array_filter(
            is_array($value) ? $value : [$value],
            static fn ($id): bool => $id !== '' && $id !== null
        ));
    }

    public function getCoverUrl(): ?string
    {
        return $this->cover_path === null ? null : Yii::getAlias('@web/' . $this->cover_path);
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->coverFile instanceof UploadedFile) {
            $dir = Yii::getAlias('@app/web/' . self::COVER_DIR);
            FileHelper::createDirectory($dir);
            // Имя генерируется, а не берётся из запроса.
            $name = Yii::$app->security->generateRandomString(16) . '.' . $this->coverFile->extension;

            if (!$this->coverFile->saveAs($dir . '/' . $name)) {
                $this->addError('coverFile', 'Не удалось сохранить обложку.');

                return false;
            }

            $this->_replacedCoverPath = $insert ? null : $this->getOldAttribute('cover_path');
            $this->cover_path = self::COVER_DIR . '/' . $name;
            $this->_uploadedCoverPath = $this->cover_path;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->_authorIds !== null) {
            // unlinkAll(false) для viaTable не удаляет строки, а пишет NULL в составной PK.
            $this->unlinkAll('authors', true);

            foreach ($this->_authorIds as $id) {
                $this->link('authors', Author::findOne((int) $id));
            }
        }

        self::deleteCover($this->_replacedCoverPath);
        $this->_replacedCoverPath = null;
        $this->_uploadedCoverPath = null;
    }

    /**
     * Файл обложки пишется в beforeSave, то есть до коммита транзакции. Если запись
     * не состоялась, файл остался бы в web/uploads сиротой — здесь он убирается.
     */
    public function save($runValidation = true, $attributeNames = null): bool
    {
        try {
            $saved = parent::save($runValidation, $attributeNames);
        } catch (\Throwable $e) {
            self::deleteCover($this->_uploadedCoverPath);
            $this->_uploadedCoverPath = null;

            throw $e;
        }

        if (!$saved) {
            self::deleteCover($this->_uploadedCoverPath);
            $this->_uploadedCoverPath = null;
        }

        return $saved;
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
        self::deleteCover($this->cover_path);
    }

    private static function deleteCover(?string $path): void
    {
        if ($path !== null && is_file($file = Yii::getAlias('@app/web/' . $path))) {
            unlink($file);
        }
    }
}
