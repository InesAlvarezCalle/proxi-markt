<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompraVenta extends Model
{
    use HasFactory;

    protected $table = 'compraventas';


    protected $fillable = [
        'id_producto',
        'id_comprador',
        'id_vendedor',
        'id_punto',
        'cantidad',
        'estado',
        'precio',
        'fecha_prevista'
    ];


    /**
     * RELACIÓN: La compraventa pertenece a un Producto específico.
     */
    public function producto() {
        // belongsTo: Relación inversa (N a 1).
        // 'id_producto' es la columna en esta tabla que apunta al 'id' de la tabla productos.
        return $this->belongsTo(Producto::class, 'id_producto', 'id');
    }

    /**
     * RELACIÓN: La compraventa pertenece a un usuario Comprador.
     */
    public function comprador() {
        // Conecta la compra con el usuario que realiza la adquisición.
        return $this->belongsTo(User::class, 'id_comprador', 'id');
    }

    /**
     * RELACIÓN: La compraventa pertenece a un usuario Vendedor.
     */
    public function vendedor() {
        // Conecta la venta con el usuario que ofrece el producto.
        return $this->belongsTo(User::class, 'id_vendedor', 'id');
    }

    /**
     * RELACIÓN: La compraventa ocurre en un Punto de Entrega específico.
     */
    public function puntoEntrega() {
        // Vincula esta transacción con el lugar físico donde se entregará el producto.
        return $this->belongsTo(PuntoEntrega::class, 'id_punto', 'id');
    }

    /**
     * RELACIÓN: Una compraventa puede tener muchas Valoraciones (1 a N).
     */
    public function valoraciones() {
        // hasMany: Indica que tras la venta se pueden generar comentarios o puntuaciones.
        // 'id_venta' es la columna en la tabla valoraciones que hace referencia a esta transacción.
        return $this->hasMany(Valoracion::class, 'id_venta');
    }
}