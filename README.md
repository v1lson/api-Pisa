# Tutorial API con Laravel
Se utilizara el ejemplo de un api con una lista de alumnos
## Creacion del proyecto 
Creamos nuevo proyecto de laravel
```bash
laravel new ApiAlumnos
``` 
A continuacion nos salen opciones de instalacion, elegimos todas por defecto.
## Creacion de base de datos
En el fichero **.env** cambiamos estos parametros
```txt
	DB_CONNECTION=mysql
	DB_HOST=127.0.0.1
	DB_PORT=23306
	DB_DATABASE=instituto
	DB_USERNAME=alumno
	DB_PASSWORD=alumno
	DB_PASSWORD_ROOT=root
	DB_PORT_PHPMYADMIN=8080
```
Creamos el **docker-compose.yaml** y le pasamos los siguientes parametros de configuracion
```txt
version: "3.8"
services:
  mysql:
    image: mysql 
    volumes: #Donde se duplican los datos
      - ./datos:/var/lib/mysql
    ports:
      - ${DB_PORT}:3306
    environment:
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD_ROOT}

  phpmyadmin:
    image: phpmyadmin
    container_name: phpmyadmin 
    ports:
      - ${DB_PORT_PHPMYADMIN}:80
    depends_on:
      - mysql
    environment:
      PMA_ARBITRARY: 1 #Para poder acceder remotamente
      PMA_HOST: mysql
```
Arrancamos el docker y nuestro proyecto
```bash
docker composer -d
``` 
```bash
php artisan serve
``` 
## Poblamos la base de datos
```bash
php artisan make:model Alumno --api -fm  
``` 
--api: Indica que este modelo se utilizará para una API.

-fm: Crea un factory y migrate para este modelo.

---
Definimos la estructura de base de datos en el fichero **database/migrations/2024_02_20_092559_create_alumnos_table.php** *(El nombre se cambiara depende del dia y nombre)*

```php
public function up(): void
{
    Schema::create('alumnos', function (Blueprint $table) {
        $table->id();
        $table->string("nombre");
        $table->string("direccion");
        $table->string("email");
        $table->timestamps();
    });
}
```
Definir los tipos de datos con cuales vamos a rellenar la base de datos en el fichero **database/factories/AlumnoFactory.php** 

```php
public function definition(): array
{
        return [
        "nombre" =>fake()->name(),
        "direccion" =>fake()->address(),
        "email" =>fake()->email()
        //
        ];
}
```
Establecemos la cantidad de datos que vamos a crear en el fichero  **database/seeders/DatabaseSeeder.php**

```php
public function run(): void
{
    Alumno::factory(20)->create();
}
```
Ejecutamos el siguiente comando en el terminal para ejecutar las configuraciones anterior mente hechas creando la base de datos y el *--seed* para generar los datos
```bash
php artisan migrate --seed 
```  
## Configuracion de API
### Ejecutamos los siguientes comandos para crear los archivos necesarios
Crea un recurso para dar forma a la salida JSON de un modelo de Alumno en tu API.
```bash 
php artisan make:resource AlumnoResource
```
Crea una colección de recursos para definir la presentación JSON de múltiples modelos de Alumno. El archivo generado estará en app/Http/Resources/AlumnoCollection.php. Este comando se usa cuando necesitas personalizar la salida JSON para una colección de modelos.

```bash
php artisan make:resource AlumnoCollection --collection
```
Define las reglas de validación para los datos recibidos en las solicitudes relacionadas con el modelo
```bash
php artisan make:request AlumnoFormRequest
```
Puedes personalizar lógica dentro del método handle para realizar acciones antes o después de la solicitud HTTP
```bash 
php artisan make:middleware HeaderMiddleware
```
### Configuracion de los ficheros
**app/Http/Resources/AlumnoResource.php**
```php
public function toArray(Request $request): array{
    return[
        "id"=>$this->id,
        "type" => "Nombre",
        "attributes" => [
            "nombre"=>$this->nombre,
            "direccion"=>$this->direccion,
            "email"=>$this->email,
        ],
        "link"=>url('api/nombre_plural'.$this->id)
    ];
}
public function with(Request $request)
    {
    return[
        "jsonapi" => [
            "version"=>"1.0"
        ]
    ];
}
```
**app/Http/Resources/AlumnoCollection.php**
```php
public function toArray(Request $request): array
{
  return parent::toArray($request);
}
public function with(Request $request)
    {
    return[
        "jsonapi" => [
            "version"=>"1.0"
        ]
    ];
}
```
**app/http/Request/AlumnoFormRequest.php**
```php
public function authorize(): bool
{
    return true;
}
public function rules(): array
{
    return [
        "data.attributes.nombre"=>"required|min:5",
        "data.attributes.direccion"=>"required",
        "data.attributes.email"=>"required|email|unique:alumnos"
    ];
}
```
**app/http/Models/Alumno.php**

 Solo los campos listados en $fillable son considerados seguros para la asignación masiva, proporcionando una capa de seguridad para evitar asignaciones no deseadas o maliciosas.
```php
protected $fillable = ["nombre","direccion","email"];
```
Definimos la ruta de nuestra api en el **routes/api.php**

```php
Route::apiResource("alumnos",AlumnoController::class);
```
**app/Http/Controllers/AlumnoController.php**
```php
/**
 * Mostrar a todos los alumnos (GET)
 */
public function index()
{
  $alumnos= Alumno::all();
  return new AlumnoCollection($alumnos);
}

/**
 * Guardar a un alumno (POST)
 */
public function store(AlumnoFormRequest $request)
{
  $datos = $request->input("data.attributes");
  $alumno = new Alumno($datos);
  $alumno->save();
  return new AlumnoResource($alumno);
}

/**
 * Mostrar a un alumno especifico (GET)
 */
public function show(int $id)
{
  $alumno = Alumno::find($id);
  if(!$alumno){
      return response()->json([
          "errors"=>[
              "status"=>404,
              "title"=>"Resource not found"
          ]
      ]);
  }
  return new AlumnoResource($alumno);
}

    /**
     * Modificar a un alumno(PUT/PATCH)
     */
public function update(Request $request, int $id)
{
    $alumno = Alumno::find($id);
    if(!$alumno){
        return response()->json([
            "errors"=>[
                "status"=>404,
                "title"=>"Resource not found"
            ]
        ]);
    }
    $verbo = $request->method();
    $rules=[];
    if ($verbo=="PUT"){
        $rules = ["data.attributes.nombre"=>"required|min:5",
        "data.attributes.direccion"=>"required",
        "data.attributes.email"=>["required","email",
            Rule::unique("alumnos","email")->ignore($alumno)]];
    }else{
        if ($request->has("data.attributes.nombre"))
            $rules["data.attributes.nombre"] = ["required", "min:5"];
        if ($request->has("data.attributes.direccion"))
            $rules["data.attributes.direccion"] = ["required"];
        if ($request->has("data.attributes.email"))
            $rules["data.attributes.email"] = ["required","email",Rule::unique("alumnos","email")->ignore($alumno)];
    }
    $datos=[];
    $datos_validados = $request->validate($rules);
    foreach ($datos_validados["data"]["attributes"] as $campo => $valor){
        $datos[$campo]=$valor;
    }
    $alumno->update($datos);
    return new AlumnoResource($alumno);
}
    /**
     * Borrar un alumno(DELETE)
     */
public function destroy(int $id)
{
$alumno = Alumno::find($id);
    if(!$alumno){
        return response()->json([
            "errors"=>[
                "status"=>404,
                "title"=>"Resource not found"
            ]
        ]);
    }
    $alumno->delete();
    return response()->json(null,204);
}
```
Controlar las excepciones en el **app/Exceptions/Handler.php**
```php
protected function invalidJson($request, ValidationException $exception): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'errors' => collect($exception->errors())->map(function ($message, $field) use
        ($exception) {
            return [
                'status' => '422',
                'title'  => 'Validation Error',
                'details' => $message[0],
                'source' => [
                    'pointer' => '/data/attributes/' . $field
                ]
            ];
        })->values()
    ], $exception->status);
}

public function render($request, Throwable $exception)
{
    // Manejo personalizado de ValidationException
    if ($exception instanceof ValidationException) {
        return $this->invalidJson($request, $exception);
    }
    // Manejo personalizado de QueryException (como ejemplo para errores de base de datos)
    if ($exception instanceof QueryException) {
        return response()->json([
            'errors' => [
                [
                    'status' => '500',
                    'title' => 'Database Error',
                    'detail' => 'Error procesando la respuesta. Inténtelo más tarde.'
                ]
            ]
        ], 500);
    }
    // Delegar a la implementación predeterminada para otras excepciones no manejadas
    return parent::render($request, $exception);
}
```
**app/Http/Middleware/HeaderMiddleware.php**
```php
public function handle($request, Closure $next)
{
    if ($request->header('accept') != 'application/vnd.api+json') {
        return response()->json([
            'error' => 'Not aceptable',
            'status' => 406,
            'details' => "Content file not specified"
        ], 406);
    }
    return $next($request);
}
```
Declaramos el middleware en el **app/Http/Kernel.php**
```php
protected $middlewareGroups = [
  'api' => [
    HeaderMiddleware::class;
  ],
];
```



