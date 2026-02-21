<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['id_comprador', 'id_vendedor', 'id_producto'];

    /**
     * RELACIÓN: Un Chat tiene muchos Mensajes (1 a N).
     */
    public function mensajes() {
        // hasMany: Indica que este chat es el contenedor de varios mensajes.
        // 'id_chat': Es la clave foránea en la tabla 'mensajes' que apunta a este chat.
        return $this->hasMany(Mensajes::class, 'id_chat');
    }

    /**
     * RELACIÓN: El Chat pertenece a un Producto (N a 1).
     */
    public function producto() {
        // belongsTo: Indica que el chat está vinculado a un producto específico.
        // 'id_producto': Es la columna en esta tabla (chats) que guarda el ID del producto.
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    /**
     * RELACIÓN: El Chat pertenece a un Comprador (N a 1).
     */
    public function comprador() {
        // belongsTo: Conecta este chat con el usuario que actúa como comprador.
        // Se usa User::class porque el comprador es un usuario de la tabla 'users'.
        return $this->belongsTo(User::class, 'id_comprador');
    }

    /**
     * RELACIÓN: El Chat pertenece a un Vendedor (N a 1).
     */
    public function vendedor() {
        // belongsTo: Conecta este chat con el usuario que actúa como vendedor.
        // Al igual que comprador, apunta al modelo User mediante su ID específico.
        return $this->belongsTo(User::class, 'id_vendedor');
    }
}