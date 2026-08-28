<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentImage extends Model
{
    const UPDATED_AT = null;

    protected $table = 'incident_images';

    protected $fillable = [
        'incident_id', 'image_path', 'uploaded_by', 'kind'
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
