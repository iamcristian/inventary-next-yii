<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "seccion".
 *
 * @property int $id
 * @property string $codigo
 * @property string $descripcion
 * @property int $almacen_id
 *
 * @property Almacen $almacen
 * @property Producto[] $productos
 */
class Seccion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seccion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigo', 'descripcion', 'almacen_id'], 'required'],
            [['almacen_id'], 'default', 'value' => null],
            [['almacen_id'], 'integer'],
            [['codigo'], 'string', 'max' => 10],
            [['descripcion'], 'string', 'max' => 255],
            [['almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'codigo' => 'Codigo',
            'descripcion' => 'Descripcion',
            'almacen_id' => 'Almacen ID',
        ];
    }

    /**
     * Gets query for [[Almacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacen()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_id']);
    }

    /**
     * Gets query for [[Productos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::class, ['seccion_id' => 'id']);
    }
}
