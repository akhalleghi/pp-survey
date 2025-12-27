<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    public function show(string $token): View
    {
        $survey = Survey::where('public_token', $token)
            ->with(['questions.options'])
            ->firstOrFail();

        return view('surveys.public-show', compact('survey'));
    }
}
