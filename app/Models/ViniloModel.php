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

    // CAMPOS PERMITIDOS
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

    // FECHAS AUTOMÁTICAS
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // REGLAS DE VALIDACIÓN COMPLETAS
    protected $validationRules = [
        'titulo'              => 'required|min_length[1]|max_length[150]',
        'artista'             => 'required|min_length[1]|max_length[150]',
        'genero'              => 'permit_empty|min_length[2]|max_length[50]',
        'formato'             => 'required|in_list[LP,Single,Maxi Single,EP,10 pulgadas,78 RPM,Picture Disc,Vinilo de Color,Flexi Disc,Shaped Disc]',
        'anio_lanzamiento'    => 'permit_empty|numeric|greater_than[1800]|less_than[2100]',
        'estado_conservacion' => 'required|in_list[M,NM,EX,VG+,VG,G,F/P]',
        'estado_adquisicion'  => 'permit_empty|in_list[Comprado Nuevo,Comprado Usado,Regalado Nuevo,Regalado Usado,Heredado,Intercambio]',
        'fecha_adquisicion'   => 'permit_empty|valid_date[Y-m-d]',
        'precio'              => 'permit_empty|numeric|greater_than_equal_to[0]'
    ];

    // MENSAJES PERSONALIZADOS DE VALIDACIÓN
    protected $validationMessages = [
        'titulo' => [
            'required' => 'El título del vinilo es obligatorio.'
        ],
        'artista' => [
            'required' => 'El nombre del artista o banda es obligatorio.'
        ],
        'formato' => [
            'required' => 'El formato del disco es obligatorio.',
            'in_list'  => 'El formato seleccionado no es válido (ej. LP, Single, EP, etc.).'
        ],
        'estado_conservacion' => [
            'required' => 'El estado de conservación es obligatorio.',
            'in_list'  => 'El estado de conservación debe ser una de las opciones válidas (M, NM, EX, VG+, VG, G, F/P).'
        ],
        'estado_adquisicion' => [
            'in_list' => 'El estado de adquisición seleccionado no es válido.'
        ],
        'fecha_adquisicion' => [
            'valid_date' => 'La fecha de adquisición debe tener el formato AAAA-MM-DD.'
        ],
        'precio' => [
            'numeric'               => 'El precio debe ser un número válido.',
            'greater_than_equal_to' => 'El precio no puede ser negativo.'
        ]
    ];

    protected $skipValidation = false;
}
