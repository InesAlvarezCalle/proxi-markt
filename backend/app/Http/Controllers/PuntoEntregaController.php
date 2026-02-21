<?php

namespace App\Http\Controllers;

// Importamos las herramientas que Laravel nos da y los modelos de nuestra base de datos.
use Illuminate\Http\Request;
use App\Models\PuntoEntrega;
use App\Models\User;
use App\Http\Requests\PuntosEntregaRequest;

class PuntoEntregaController extends Controller
{
    // Función para mostrar absolutamente todos los puntos de entrega que existen.
    public function index() {
        // Busca todos los puntos de entrega y además trae ('with') la información del usuario dueño de cada uno.
        $puntos = PuntoEntrega::with('usuario')->get();
        
        // Lo devuelve todo en formato JSON.
        return response()->json($puntos);
    }

    /**
     * Listar puntos de entrega de un vendedor específico
     */
    // Recibe el modelo 'User' directamente (Route Model Binding). Laravel busca al usuario automáticamente por la URL.
    public function puntosPorVendedor(Request $request, User $usuario) {
        // Busca solo los puntos de entrega cuyo 'id_usuario' coincida con el id del usuario que pasamos por la URL.
        $puntos = PuntoEntrega::where('id_usuario', $usuario->id)->get();
        
        // Devuelve la lista en formato JSON.
        return response()->json($puntos);
    }

    /**
     * Crear un nuevo punto de entrega (Para el agricultor)
     */
    // Usamos 'PuntosEntregaRequest' en vez de 'Request'. Esto significa que Laravel valida los datos ANTES de siquiera entrar a esta función.
    public function store(PuntosEntregaRequest $request) {
        // 1. Los datos ya vienen validados gracias al Request (si estuvieran mal, Laravel ya habría devuelto un error automático).
        
        // 2. Extraemos el usuario del token de Sanctum (sabemos exactamente quién está logueado).
        $user = $request->user();

        // 3. Creamos el punto asociándolo al ID del usuario autenticado.
        $punto = PuntoEntrega::create([
            'id_usuario' => $user->id, // Asignamos el dueño automáticamente para que no pueda falsificarlo.
            'nombre_punto' => $request->nombre_punto,
            'direccion_punto' => $request->direccion_punto,
            'longitud' => $request->longitud,
            'latitud' => $request->latitud,
        ]);

        // Devolvemos respuesta indicando que fue un éxito (201 significa 'Creado').
        return response()->json([
            'status' => true,
            'message' => 'Punto de entrega creado correctamente',
            'data' => $punto
        ], 201);
    }

    /**
     * Eliminar un punto de entrega
     */
    public function destroy(Request $request, $id) {
        // Obtenemos al usuario que está intentando hacer el borrado.
        $user = $request->user();
        
        // Buscamos el punto que coincida con el ID Y que pertenezca al usuario autenticado. (Doble capa de seguridad).
        $punto = PuntoEntrega::where('id', $id)->where('id_usuario', $user->id)->first();

        // Si la búsqueda devuelve vacío (porque el punto no existe o porque es de otra persona).
        if (!$punto) {
            // Le denegamos el paso con un error 404.
            return response()->json(['message' => 'No se encontró el punto o no tienes permiso'], 404);
        }

        // Si pasó el filtro, lo borramos de verdad.
        $punto->delete();

        // Confirmamos que se borró correctamente.
        return response()->json(['message' => 'Punto de entrega eliminado']);
    }

    // Función súper potente para buscar puntos de entrega cercanos basándonos en coordenadas.
    public function puntosRadio(Request $request, $radio) {
        // Capturamos la latitud y longitud que el usuario pasa por la URL (ej: ?lat=40.41&lng=-3.70).
        $latUsuario = $request->query('lat');
        $lngUsuario = $request->query('lng');
        
        // Vemos quién es el usuario que está buscando.
        $usuario = $request->user();
        
        // Constante matemática: El radio de la Tierra en kilómetros.
        $radioTierra = 6371;

        // Si se le olvidó enviar la latitud o la longitud en la URL, cortamos el proceso devolviendo un error 400.
        if (!$latUsuario || !$lngUsuario) {
            return response()->json(['error' => 'Faltan coordenadas'], 400);
        }

        // Empezamos a construir la consulta a la base de datos.
        $puntos = PuntoEntrega::select('*')
            // selectRaw nos permite inyectar código SQL puro. 
            // Esta gran fórmula matemática (Fórmula de Haversine) calcula la distancia real en KM entre las coordenadas del usuario y las del punto en la BD.
            ->selectRaw(
                "( ? * acos( cos( radians(?) ) * cos( radians( latitud ) ) * cos( radians( longitud ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitud ) ) ) ) AS distancia",
                [$radioTierra, $latUsuario, $lngUsuario, $latUsuario] // Rellenamos las interrogaciones de la fórmula con nuestras variables.
            )
            // Además, nos traemos los datos básicos del dueño del punto y los productos que hay en él.
            ->with([
                'usuario:id,nombre_usuario', // Trae solo el id y el nombre del usuario para no recargar la respuesta.
                'productos'
            ])
            // Filtramos ('having') para que SOLO nos devuelva los que estén a una distancia MENOR O IGUAL al radio que pedimos.
            ->having('distancia', '<=', $radio)
            // Los ordenamos de más cercanos a más lejanos.
            ->orderBy('distancia', 'asc')
            // Excluimos los puntos de entrega que sean nuestros (para no comprarnos a nosotros mismos).
            ->where('id_usuario', '!=', $usuario->id)
            ->get(); // Ejecutamos la búsqueda.

        // Devolvemos los puntos cercanos en formato JSON.
        return response()->json($puntos);
    }
}