<?php

namespace app\controllers;

use Yii;
use app\models\Producto;

use Exception;
use yii\data\Pagination;
use yii\db\IntegrityException;

class ProductoController extends \yii\web\Controller
{
    // Sobrescribe la función behaviors de la clase padre Controller
    public function behaviors()
    {
        // Obtenemos la función original de la clase padre
        $behaviors = parent::behaviors();
        
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => ['get'],
                'view' => ['get'],
                'create' => ['post'],
                'update' => ['post', 'put'],
                'delete' => ['post', 'delete']
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
     * El index devuelve la lista de productos
     * La acción de un controlador con nombre "Index" puede accederse de dos maneras ejm:
     * 1.- localhost:8888/producto/index
     * 2.- localhost:888/producto
     * 
     * @param int $pageSize es la cantidad de registros que devolverá por pagina
     * @return array Array asociativo con la lista de productos y datos de la paginación
     */
    public function actionIndex($pageSize = 10)
    {
        $query = Producto::find()
            ->select(['producto.*', 'marca.nombre AS marca', 'seccion.descripcion AS seccion'])
            ->leftJoin('marca', 'marca.id=producto.marca_id')
            ->leftJoin('seccion', 'seccion.id=producto.seccion_id');

        // Pagination esta a la escucha del parámetro "page" ejm: localhost:8888/producto?page=2
        $pagination = new Pagination([
            'pageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        // Consulta con la paginación
        $productos = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        /**
         * Armamos la paginación con la información necesaria
         */
        $currentPage = $pagination->getPage()+1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'data' => $productos,
            'pagination' => [
                'previousPage' => $currentPage > 1 ? $currentPage-1 : null,
                'currentPage' => $currentPage,
                'nextPage' => $currentPage < $totalPages ? $currentPage+1 : null,
                'totalPages' => $totalPages,
                'pageSize' => $pageSize,
                'totalCount' => $pagination->totalCount
            ]
        ];
        return $response;
    }

    /**
     * Devuelve la información de un producto
     * 
     * @param int $id ID del producto a obtener su información
     * @return array Array asociativo con la información del producto
     */
    public function actionView($id)
    {
        $model = Producto::findOne($id);
        if ($model) {
            $response = [
                'success' => true,
                'producto' => $model
            ];
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
     * Crea un nuevo registro
     * 
     * @return array Array asociativo con un mensaje de respuesta
     */
    public function actionCreate()
    {
        // Obtener datos
        $params = Yii::$app->request->bodyParams;
        $model = new Producto();

        // Cargar datos
        $model->load($params, '');
        $model->fecha_creacion = date('Y-m-d H:i:s');

        // Guardar datos
        if ($model->save()) {
            // Establece el código de estado como 201 para registro creado
            Yii::$app->getResponse()->setStatusCode(201);
            $response = [
                'success' => true,
                'message' => 'El producto se creo con éxito.',
                'data' => $model
            ];
        } else {
            // Establece el código de estado como 422 para validación de datos fallida
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => 'Hay campos con errores',
                'errors' => $model->errors
            ];
        }

        return $response;
    }

    /**
     * Actualiza la información del registro
     * 
     * @param int $id ID del registro que se editara
     * @return array Array asociativo con un mensaje de respuesta
     */
    public function actionUpdate($id)
    {
        // Obtener los datos
        $params = Yii::$app->request->bodyParams;

        // Buscar el registro
        $model = Producto::findOne($id);

        if ($model) {
            // Si existe cargar los datos
            $model->load($params, '');
            $model->fecha_creacion = date('Y-m-d H:i:s');

            // Guardar datos
            if ($model->save()) {
                // save() es simple devuelve true o false
                // if ($model->update()) {
                // update() devuelve true o false, además puede lanzar excepciones y sigue más pasos para guardar
                // Referencia update https://www.yiiframework.com/doc/api/2.0/yii-db-baseactiverecord#update()-detail
                $response = [
                    'success' => true,
                    'message' => 'El producto se creo con éxito.',
                    'data' => $model
                ];
            } else {
                // Establece el código de estado como 422 para validación de datos fallida
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
                $response = [
                    'success' => false,
                    'message' => 'Hay campos con errores',
                    'errors' => $model->errors
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
     * Elimina un registro
     * 
     * @param int $id ID del registro a eliminar
     * @return array Array asociativo con un mensaje de respuesta 
     */
    public function actionDelete($id)
    {
        $model = Producto::findOne($id);
        if ($model) {
            try {
                $model->delete();
                $response = [
                    'success' => false,
                    'message' => 'El producto fue eliminado con éxito.'
                ];
            } catch (IntegrityException $ie) {
                // Establece el código de estado como 500 para error de servidor
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'El producto se encuentra en uso.',
                    'code' => $ie->getCode(),
                ];
            } catch (Exception $e) {
                // Establece el código de estado como 500 para error de servidor
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Producto no encontrado.'
            ];
        } 
        return $response;
    }

}
