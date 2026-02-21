<?php

namespace App\Http\Controllers;

// Importamos las clases y modelos que vamos a necesitar usar en este controlador.
use Illuminate\Http\Request; 
use App\Models\Producto;
use App\Models\PuntoEntrega;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller {
    // Función para mostrar la lista de productos
    public function index(Request $request) {
        // Preparamos la consulta. 'with' hace que también traiga los datos de la categoría, el dueño y el punto de entrega.
        $query = Producto::with(['categoria', 'usuario', 'punto_entrega']);

        // Si el usuario envió una palabra en 'search' (y no está vacía), filtramos por el nombre del producto.
        if ($request->has('search') && $request->search != '') {
            // Busca productos que contengan la palabra ingresada en cualquier parte de su nombre.
            $query->where('nombre_producto', 'like', '%' . $request->search . '%');
        }

        // Si el usuario envió algo en 'categorias' (y no está vacío), aplicamos el filtro de categoría.
        if ($request->has('categorias') && $request->categorias != '') {
            // Convertimos las categorías a un arreglo (ya sea que vengan como texto separado por comas o como arreglo).
            $categorias = is_array($request->categorias) 
                          ? $request->categorias 
                          : explode(',', $request->categorias);
                          
            // Filtramos buscando solo los productos cuya categoría coincida con las enviadas en el arreglo.
            $query->whereHas('categoria', function($q) use ($categorias) {
                $q->whereIn('nombre_categoria', $categorias);
            });
        }
        
        // Ejecuta la consulta final y divide los resultados en "páginas" de 9 en 9.
        $productos = $query->paginate(9);

        // Devuelve los productos encontrados al usuario en formato JSON.
        return response()->json($productos);
    }

    // Función para que un usuario vea exclusivamente sus propios productos.
    public function productosPorUsuario(Request $request) {
        // Obtenemos los datos del usuario que está logueado haciendo la petición.
        $user = $request->user();

        // Buscamos los productos en la BD, incluyendo su categoría.
        $productos = Producto::with('categoria')
            ->where('id_usuario', $user->id) // Filtramos solo por los que le pertenecen a este usuario.
            ->orderBy('created_at', 'desc') // Ordenamos del más nuevo al más viejo.
            ->paginate(7); // Los mostramos de 7 en 7.
    
        // Devolvemos el resultado en formato JSON.
        return response()->json($productos);
    }

    /**
     * Crear un nuevo producto (Para el agricultor)
     */
    public function store(Request $request) {
        // Validamos que los datos que envía el usuario sean correctos y cumplan estas reglas.
        $validado = $request->validate([
            'id_categoria' => 'required|exists:categorias,id', // Debe existir en la tabla categorias.
            'id_puntoentrega' => 'required|exists:puntos_entrega,id', // Debe existir en puntos_entrega.
            'nombre_producto' => 'required|string|max:255', // Es obligatorio y máximo 255 letras.
            'descripcion' => 'nullable|string', // Es opcional.
            'precio' => 'required|numeric|min:0', // Obligatorio y no puede ser un número negativo.
            'stock_total' => 'required|integer|min:1', // Obligatorio, mínimo 1 en inventario.
            'imagen' => 'nullable|image|max:2048', // Opcional, debe ser imagen y pesar máximo 2MB.
        ]);

        // Obtenemos quién es el usuario logueado actualmente.
        $user = $request->user();

        // Le asignamos a los datos validados el ID de ese usuario de forma automática.
        $validado['id_usuario'] = $user->id;
        // Si no nos envían un estado, le ponemos "disponible" por defecto.
        $validado['estado'] = $validado['estado'] ?? 'disponible';

        // Comprobamos si nos enviaron un archivo llamado "imagen".
        if ($request->hasFile('imagen')) {
            // Si la enviaron, la guardamos físicamente en la carpeta "productos" del disco público.
            $validado['imagen'] = $request->file('imagen')->store('productos', 'public');
        } else {
            // Si no subieron ninguna foto, le asignamos una imagen genérica por defecto.
            $validado['imagen'] = 'productos/default.png';
        }

        // Mandamos a crear e insertar el producto en la base de datos usando nuestro arreglo.
        Producto::create($validado);

        // Devolvemos un mensaje de éxito junto con los datos que acabamos de guardar (Código 201: Creado).
        return response()->json([
            'message' => 'Producto publicado con éxito',
            'producto' => $validado
        ], 201);
    }

    /**
     * Ver detalle de un producto específico
     */
    public function show($id){
        // Busca el producto por ID y trae sus relaciones. Si no lo encuentra, lanza un error 404 automático.
        $producto = Producto::with('categoria', 'usuario', 'punto_entrega')->findOrFail($id);
        
        // Devuelve toda la información de ese producto en JSON.
        return response()->json($producto);
    }

    /**
     * Actualizar datos del producto
     */
    public function update(Request $request, $id){
        // Primero verificamos que el producto que queremos editar exista.
        $producto = Producto::findOrFail($id);

        // Validamos los datos nuevos que nos están enviando.
        $data = $request->validate([
            'nombre_producto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'numeric|min:0',
            'stock_total' => 'integer|min:0',
            'id_puntoentrega' => 'required|exists:puntos_entrega,id',
            'imagen' => 'nullable|image|max:2048'
        ]);

        // Si nos envían una imagen nueva para reemplazar la vieja...
        if ($request->hasFile('imagen')) {
            // Revisamos si el producto ya tenía una imagen guardada previamente.
            if ($producto->imagen) {
                // Borramos físicamente la imagen vieja del disco para no ocupar espacio basura.
                Storage::disk('public')->delete($producto->imagen);
            }

            // Guardamos la nueva imagen y actualizamos la ruta.
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        // Le aplicamos todos los cambios validados al producto que encontramos al inicio.
        $producto->update($data);

        // Devolvemos el mensaje de éxito y el producto ya modificado.
        return response()->json([
            'message' => 'Producto actualizado con éxito',
            'producto' => $producto
        ], 200);
    }

    // Función para borrar un producto.
    public function destroy($id) 
    {
        // Busca el producto por ID; falla automáticamente si no existe.
        $producto = Producto::findOrFail($id);

        // Lo elimina de la base de datos.
        $producto->delete();

        // Devuelve una confirmación de éxito.
        return response()->json(['message' => 'Producto eliminado correctamente'], 200);
    }

    // Función que devuelve los productos disponibles en un punto de entrega específico.
    public function obtenerProductosPunto(PuntoEntrega $punto) 
    {
        // Buscamos todos los productos que pertenezcan a ese punto de entrega.
        $productos = Producto::with(['categoria', 'punto_entrega'])
            ->where('id_puntoentrega', $punto->id)
            ->get(); // 'get' trae todos los resultados sin paginar.

        // Si la lista de productos resultó estar vacía...
        if ($productos->isEmpty()) {
            // Devolvemos un mensaje avisando que no hay productos y código 404 (No encontrado).
            return response()->json([
                'status' => false,
                'message' => 'No hay productos para este punto de entrega.'
            ], 404);
        }

        // Si sí hay productos, los devolvemos indicando que todo salió bien.
        return response()->json([
            'status' => true,
            'productos' => $productos
        ]);
    }
}