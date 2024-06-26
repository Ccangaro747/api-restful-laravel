<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Helpers\JwtAuth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function pruebas(Request $request)
    {
        return "Acción de pruebas de UserController";
    }

    public function register(Request $request)
    {
        // Recoger los datos del usuario por POST
        $json = $request->input('json', null);
        $params = json_decode($json); // objeto
        $paramsArray = json_decode($json, true); // array

        if (!empty($params) && !empty($paramsArray)) {
            // Limpiar datos
            $paramsArray = array_map('trim', $paramsArray);

            // Validar los datos
            $validator = Validator::make($paramsArray, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users', // Comprobar si el usuario existe (duplicado)
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                // La validación ha fallado
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors()
                ];
            } else {
                // Validación pasada correctamente

                // Cifrar la contraseña
                $pwd = Hash::make($params->password);

                // Crear el usuario
                $user = new User();
                $user->name = $paramsArray['name'];
                $user->surname = $paramsArray['surname'];
                $user->email = $paramsArray['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                // Guardar el usuario en la base de datos
                $user->save();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            ];
        }

        return response()->json($data, $data['code']);
    }

    // Método para loguear al usuario y conseguir el token de autenticación de usuario identificado por JWT (Json Web Token)
    public function login(Request $request)
    {
        $jwtAuth = new JwtAuth(); // Instanciar la clase JwtAuth para poder usar sus métodos y propiedades en este controlador UserController


        // Recibir los datos por POST

        $json = $request->input('json', null);
        $params = json_decode($json); // objeto
        $paramsArray = json_decode($json, true); // array

        // Validar los datos

        $validator = Validator::make($paramsArray, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            // La validación ha fallado
            $singup = [
                'status' => 'error',
                'code' => 400,
                'message' => 'El usuario no se ha podido logear',
                'errors' => $validator->errors()
            ];
        } else {
            // Validación pasada correctamente

            // Cifrar la contraseña

            $pwd = Hash::make($params->password);

            // Devolver el token o los datos decodificados según corresponda

            $singup = $jwtAuth->singup($params->email, $params->password);
            if (!empty($params->getToken)) {
                $singup = $jwtAuth->singup($params->email, $params->password, true);
            }
        }

        return response()->json($singup, 200); // Llamar al método singup de la clase JwtAuth para loguear al usuario y conseguir el token de autenticación de usuario identificado por JWT (Json Web Token)
    }

    // Método para actualizar los datos del usuario identificado por JWT (Json Web Token)
    public function update(Request $request)
    {
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth(); // Instanciar la clase JwtAuth para poder usar sus métodos y propiedades en este controlador UserController
        $checkToken = $jwtAuth->checkToken($token); // Obtener el usuario identificado

        //Recoger los datos por POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        // Verificar si el usuario está autenticado
        if ($checkToken && !empty($params_array)) {

            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //Validar datos
            $validate = Validator::make($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users,' . $user->sub
            ]);

            //Quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);


            //Actualizar el usuario en la base de datos
            $user_update = User::where('id', $user->sub)->update($params_array);


            //Devolver array con el resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            //Si el token no es válido, se devuelve un mensaje de error
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'El usuario no está identificado'
            ];
        }
        return response()->json($data, $data['code']); // Devolver el mensaje de error en formato json.
    }


    // Método para subir la imagen del usuario identificado por JWT (Json Web Token)
    public function upload(Request $request)
    {
        //Recoger los datos de la peticion es decir el archivo que se esta subiendo por POST
        $image = $request->file('file0');


        //Validar la imagen
        $validate = Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);


        //Guardar imagen
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();
            Storage::disk('users')->put($image_name, File::get($image));

            //Devolver el resultado
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        return response()->json($data, $data['code']);
    }

    // Método para obtener la imagen del usuario identificado por JWT (Json Web Token)
    public function getImage($filename)
    {
        //Comprobar si existe el fichero
        $isset = Storage::disk('users')->exists($filename);

        if ($isset) {
            //Conseguir la imagen
            $file = Storage::disk('users')->get($filename);

            //Devolver la imagen
            return new Response($file, 200);
        } else {
            //Mostrar un error
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    // Método para obtener los datos de un usuario
    public function detail($id)
    {
        $user = User::find($id);

        if (is_object($user)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'user' => $user
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }
}
