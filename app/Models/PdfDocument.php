<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PdfDocument extends Model
{
    use HasFactory;

    protected $table = 'pdf_documents';

    protected $fillable = [
        'name',
        'description',
        'filename',
        'total_pages',
        'markers',
        'page_markers',
        'marker_counter',
        'saved_at'
    ];

    protected $casts = [
        'markers' => 'array',
        'page_markers' => 'array',
        'saved_at' => 'datetime'
    ];
}
