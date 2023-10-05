<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "producto".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property float $precio
 * @property int $stock
 * @property string $fecha_creacion
 * @property string|null $fecha_actualizacion
 * @property int $marca_id
 * @property int $seccion_id
 *
 * @property Marca $marca
 * @property ProductoCategoria[] $productoCategorias
 * @property Seccion $seccion
 */
class Producto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'producto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'precio', 'marca_id', 'seccion_id'], 'required'],
            [['nombre', 'descripcion'], 'string'],
            [['precio'], 'number'],
            [['stock', 'marca_id', 'seccion_id'], 'default', 'value' => null],
            [['stock', 'marca_id', 'seccion_id'], 'integer'],
            // [['fecha_creacion', 'fecha_actualizacion'], 'safe'],
            [['marca_id'], 'exist', 'skipOnError' => true, 'targetClass' => Marca::class, 'targetAttribute' => ['marca_id' => 'id']],
            [['seccion_id'], 'exist', 'skipOnError' => true, 'targetClass' => Seccion::class, 'targetAttribute' => ['seccion_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'descripcion' => 'Descripcion',
            'precio' => 'Precio',
            'stock' => 'Stock',
            'fecha_creacion' => 'Fecha Creacion',
            'fecha_actualizacion' => 'Fecha Actualizacion',
            'marca_id' => 'Marca ID',
            'seccion_id' => 'Seccion ID',
        ];
    }

    /**
     * Gets query for [[Marca]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMarca()
    {
        return $this->hasOne(Marca::class, ['id' => 'marca_id']);
    }

    /**
     * Gets query for [[ProductoCategorias]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductoCategorias()
    {
        return $this->hasMany(ProductoCategoria::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[Seccion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSeccion()
    {
        return $this->hasOne(Seccion::class, ['id' => 'seccion_id']);
    }


    // Aumentado manualmente
    // Referencia: https://www.yiiframework.com/doc/guide/2.0/en/db-active-record#junction-table
    /**
     * Gets query for [[Categorias]].
     * Relación muchos a muchos de productos con categorías
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategorias()
    {
        return $this->hasMany(Categoria::class, ['id' => 'categoria_id'])
            ->viaTable('producto_categoria', ['producto_id' => 'id']);
    }
}
