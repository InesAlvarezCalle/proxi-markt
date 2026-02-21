<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias'; 

    protected $fillable = [
        'nombre_categoria'
    ];
    
    protected $hidden = ["updated_at", "created_at"];

    /**
     * RELACIÓN: Una categoría tiene muchos productos (1 a N).
     */
    public function productos()
    {
        // hasMany: Define que este modelo (Categoría) es el "padre".
        // Producto::class: Es el modelo con el que se conecta.
        // 'id_categoria': Es la llave foránea (foreign key) que Laravel debe buscar en la tabla 'productos'.
        return $this->hasMany(Producto::class, 'id_categoria');
    }
}