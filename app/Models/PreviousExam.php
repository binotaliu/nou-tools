<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreviousExam extends Model
{
    protected $fillable = [
        'course_name',
        'course_no',
        'term',
        'midterm_reference_primary',
        'midterm_reference_secondary',
        'final_reference_primary',
        'final_reference_secondary',
    ];
}
