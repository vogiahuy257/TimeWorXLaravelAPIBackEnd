<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SummaryReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'summary_report_id';

    protected $fillable = [
        'project_id',
        'project_name',
        'project_description',
        'name',
        'reported_by_user_id',
        'summary',
        'completed_tasks',
        'upcoming_tasks',
        'project_issues',
        'zip_name',
        'zip_file_path',
        'report_date',
    ];

    protected $dates = ['deleted_at', 'report_date'];

    /**
     * Relationship với User
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by_user_id', 'id');
    }

}
