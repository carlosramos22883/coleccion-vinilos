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

    // Declaramos las propiedades vacías para llenarlas dinámicamente
    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Constructor del Modelo
     * Aquí hacemos que el año máximo sea siempre el año actual del servidor.
     */
    public function __construct()
    {
        parent::__construct();

        $anioActual = date('Y');

        $this->validationRules = [
            'titulo'              => 'required|min_length[1]|max_length[150]',
            'artista'             => 'required|min_length[1]|max_length[150]',
            'genero'              => 'permit_empty|min_length[2]|max_length[50]',
            'formato'             => 'required|in_list[LP,Single,Maxi Single,EP,10 pulgadas,78 RPM,Picture Disc,Vinilo de Color,Flexi Disc,Shaped Disc]',
            'anio_lanzamiento'    => "permit_empty|integer|greater_than_equal_to[1946]|less_than_equal_to[{$anioActual}]",
            'estado_conservacion' => 'required|in_list[M,NM,EX,VG+,VG,G,F/P]',
            'estado_adquisicion'  => 'permit_empty|in_list[Comprado Nuevo,Comprado Usado,Regalado Nuevo,Regalado Usado,Heredado,Intercambio]',
            'fecha_adquisicion'   => 'permit_empty|valid_date[Y-m-d]',
            // ✅ Precio: máximo 2 decimales, sin notación científica, entre 0 y 4,000,000
            'precio'              => "permit_empty|regex_match[/^\d{1,8}(\.\d{1,2})?$/]|less_than_equal_to[4000000]|greater_than_equal_to[0]"
        ];

        $this->validationMessages = [
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
            'anio_lanzamiento' => [
                'integer'               => 'El año debe ser un número entero.',
                'greater_than_equal_to' => 'El año no puede ser anterior a 1946 (invención del vinilo).',
                'less_than_equal_to'    => "El año no puede ser mayor al año actual ({$anioActual})."
            ],
            'fecha_adquisicion' => [
                'valid_date' => 'La fecha de adquisición debe tener el formato AAAA-MM-DD.'
            ],
            'precio' => [
                'regex_match'         => 'El precio debe tener máximo 2 decimales y no puede contener notación científica (ej. 1e10).',
                'less_than_equal_to'  => 'El precio no puede exceder $4,000,000.00 (récord histórico de un vinilo).',
                'greater_than_equal_to' => 'El precio no puede ser negativo.'
            ]
        ];
    }
}
