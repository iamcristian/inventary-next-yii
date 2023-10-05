<?php

namespace app\controllers;

use app\models\Categoria;
use Yii;
use app\models\Producto;
use app\models\Seccion;
use app\models\Marca;

use Exception;

class ApiController extends \yii\web\Controller
{

    // Sobrescribe la función behaviors de la clase padre Controller
    public function behaviors()
    {
        // Obtenemos la función original de la clase padre
        $behaviors = parent::behaviors();
        
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'get-seccion' => ['get'],
                'get-total-products-by-brand' => ['get'],
                'product-with-max-stock' => ['get'],
                'verify-product-stock' => ['get'],
                'assign-category' => ['get'],
                'unassign-category' => ['get'],
            ]
        ];

        return $behaviors;
    }

    // Sobrescribe la función beforeAction de la clase padre Controller
    /**
     * La función beforeAction se ejecuta antes de cada acción de este controlador
     */
    public function beforeAction($action)
    {
        // Establece el formato de respuesta como formato JSON
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Deshabilita la validación por CSRF 
        $this->enableCsrfValidation = false;

        // Devuelve la función original de la clase padre
        return parent::beforeAction($action);
    }

    /**
     * Devuelve una sección con todos sus productos
     * 
     * @param int $id ID de la sección a obtener su información
     * @return array Array asociativo con la información de la sección y sus productos
     */
    public function actionGetSeccion($id)
    {
        $model = Seccion::findOne($id);
        if ($model) {
            // El método getProductos() se genera en el modelo
            $productos = $model->getProductos()->all();
            $response = [
                'success' => true,
                'seccion' => $model,
                'productos' => $productos
            ];
        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Sección no encontrado.'
            ];
        }

        /* 
        * Alternativamente se puede

        $model = Seccion::find()
            ->where(['seccion.id' => $id])
            ->joinWith('productos')
            ->asArray()
            ->all();
        if ($model) {
            $response = [
                'success' => true,
                'seccion' => $model
            ];
        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Sección no encontrado.'
            ];
        }

        */

        return $response;
    }

    /**
     * Devuelve la cantidad total de productos de una marca
     * suma de stocks de los productos de una marca
     * 
     * @param int $id ID de la marca
     * @return array Array asociativo con la cantidad total de productos
     */
    public function actionGetTotalProductsByBrand($id)
    {
        $model = Marca::findOne($id);
        if ($model) {

            $productos = $model->getProductos()->all();
            $total = 0;
            // Iteración de lista de productos, cada item de la lista se trata como un objeto
            foreach ($productos as $producto) {
                $total = $total + $producto->stock;
            }
            
            /*
            * Alternativamente
            $total = $model->getProductos()->sum('stock');

             */

            $response = [
                'success' => true,
                'message' => "Cantidad total de productos de la marca {$model->nombre}",
                'total' => $total
            ];
        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'marca no encontrado.'
            ];
        }
        return $response;
    }

    /**
     * Devuelve los productos con mayor stock
     * 
     * @return array Array asociativo con el producto con mayor stock
     */
    public function actionProductWithMaxStock()
    {
        $max = Producto::find()->max('stock');
        $model = Producto::find()->where("stock={$max}")->all();
        // Consulta equivalente
        // $model = Producto::findAll("stock={$max}");

        /*
         * Equivalente
        $model = Producto::find()->where('stock = (SELECT max(stock) FROM producto)')->all();
        $response = [
            'success' => true,
            'message' => "Lista de productos con el mayor stock.",
            'productos' => $model
        ];
         */

        $response = [
            'success' => true,
            'message' => "Lista de productos con el mayor stock = {$max}.",
            'productos' => $model
        ];
        return $response;
    }

    /**
     * Devuelve si un producto tiene stock (stock > 0)
     * 
     * @param int $id ID del producto
     * @return array Array asociativo con el mensaje si el producto tiene stock
     */
    public function actionVerifyProductStock($id)
    {
        $model = Producto::findOne($id);
        if ($model) {

            // hasStock sera un boolean
            $hasStock = $model->stock > 0;
            $response = [
                'success' => true,
                'message' => $hasStock ? 'El producto tiene stock.' : 'El producto no tiene stock.',
                'data' => [
                    'hasStock' => $hasStock,
                    'stock' => $model->stock
                ]
            ];

            /*
             * La estructura de una respuesta depende como se quiera manejar
             * Tomar en cuenta que debemos facilitar el acceso a los datos en frontend
            $response = [
                'success' => true,
                'message' => $hasStock ? 'El producto tiene stock.' : 'El producto no tiene stock.',
                'hasStock' => $hasStock,
                'stock' => $model->stock,
            ];
             */


        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Producto no encontrado.'
            ];
        }
        return $response;
    }

    /**
     * Asigna una categoría a un producto
     * 
     * @param int $producto_id ID del producto al que se le asignara la categoría
     * @param int $categoria_id ID de la categoría que sera asignada al producto
     * @return array Array asociativo con un mensaje del estado de la asignación 
     */
    public function actionAssignCategory($producto_id, $categoria_id)
    {
        $product = Producto::findOne($producto_id);
        if ($product) {

            $category = Categoria::findOne($categoria_id);
            if ($category) {

                if (!$product->getCategorias()->where("id={$categoria_id}")->one()) {
                    // Si no existe el enlace entre el producto y la categoría

                    try {
                        // Enlaza el producto con la categoría
                        // Usa la relación muchos a muchos del modelo Producto linea 108
                        $product->link('categorias', $category);
                        $response = [
                            'success' => true,
                            'message' => 'Se asigno la categoría al producto correctamente.'
                        ];
                    } catch (Exception $e) {
                        // Establece el código de estado como 500 para error de servidor
                        Yii::$app->getResponse()->setStatusCode(500);
                        $response = [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ];
                    }

                } else {
                    // Establece el código de estado como 422 para Existing link.
                    Yii::$app->getResponse()->setStatusCode(422, 'Existing link.');
                    // Si el enlace entre producto y categoría existe
                    $response = [
                        'success' => false,
                        'message' => 'El producto ya posee la categoría.'
                    ];
                }

            } else {
                // Si no existe lanzar error 404
                Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Categoría no encontrado.'
            ];
            }

        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Producto no encontrado.'
            ];
        }
        return $response;
    }

    /**
     * Desasigna una categoría a un producto
     * 
     * @param int $producto_id ID del producto al que se le desasignara la categoría
     * @param int $categoria_id ID de la categoría que sera desasignada al producto
     * @return array Array asociativo con un mensaje del estado de la desasignación 
     */
    public function actionUnassignCategory($producto_id, $categoria_id)
    {
        $product = Producto::findOne($producto_id);
        if ($product) {

            $category = Categoria::findOne($categoria_id);
            if ($category) {

                if ($product->getCategorias()->where("id={$categoria_id}")->one()) {
                    // Si existe el enlace entre el producto y la categoría

                    try {
                        // Desenlaza el producto con la categoría
                        // Usa la relación muchos a muchos del modelo Producto linea 108
                        // tercer parámetro de unlink en true elimina el registro de la tabla producto_categoria
                        $product->unlink('categorias', $category, true);
                        $response = [
                            'success' => true,
                            'message' => 'Se desasigno la categoría al producto correctamente.'
                        ];
                    } catch (Exception $e) {
                        // Establece el código de estado como 500 para error de servidor
                        Yii::$app->getResponse()->setStatusCode(500);
                        $response = [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ];
                    }

                } else {
                    // Establece el código de estado como 422 para Existing link.
                    Yii::$app->getResponse()->setStatusCode(422, 'Existing link.');
                    // Si el enlace entre producto y categoría no existe
                    $response = [
                        'success' => false,
                        'message' => 'El producto no posee la categoría.'
                    ];
                }

            } else {
                // Si no existe lanzar error 404
                Yii::$app->getResponse()->setStatusCode(404);
                $response = [
                    'success' => false,
                    'message' => 'Categoría no encontrada.'
                ];
            }

        } else {
            // Si no existe lanzar error 404
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Producto no encontrado.'
            ];
        }
        
        return $response;
    }

}