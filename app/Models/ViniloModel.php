<?php

namespace App\Models;

use CodeIgniter\Model;

class ViniloModel extends Model
{
    protected $table            = 'vinilos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'titulo',
        'artista',
        'anio_lanzamiento',
        'genero',
        'formato',
        'estado_conservacion',
        'estado_adquisicion',
        'fecha_adquisicion',
        'precio'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'titulo'             => 'required|min_length[1]|max_length[150]',
        'artista'            => 'required|min_length[1]|max_length[150]',
        'anio_lanzamiento'   => 'permit_empty|numeric|greater_than[1800]|less_than[2100]',
        'estado_adquisicion' => 'permit_empty|in_list[Comprado Nuevo,Comprado Usado,Regalado Nuevo,Regalado Usado,Heredado,Intercambio]',
        'fecha_adquisicion'  => 'permit_empty|valid_date[Y-m-d]',
        'precio'             => 'permit_empty|numeric'
    ];
}
