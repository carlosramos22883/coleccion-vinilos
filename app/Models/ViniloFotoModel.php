<?php

namespace App\Models;

use CodeIgniter\Model;

class ViniloFotoModel extends Model
{
    protected $table            = 'vinilo_fotos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'vinilo_id',
        'ruta_foto',
        'es_portada'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
