<?php

namespace App\Features\Journal;

use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class JournalController extends Controller {
    const string page_list   = 'journal/journal-list';
    const string page_detail = 'journal/journal-detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Journal des actions",
            'breadcrumbs' => [['title' => "Journal des actions", 'href' => route('journal.list')]],
            'table'       => JournalService::get_table(),
        ]);
    }



    public function detail(string $cle) : InertiaResponse {
        $journal = JournalService::get_or_fail($cle);

        return Inertia::render(self::page_detail, [
            'mode_vue'    => RendersService::mode_consultation,
            'titre_page'  => "{$journal->action_render} — {$journal->entite_render}",
            'breadcrumbs' => [
                ['title' => "Journal des actions", 'href' => route('journal.list')],
                ['title' => $journal->date_render, 'href' => route('journal.detail', ['cle' => $cle])],
            ],
            'item'        => [
                'date'         => $journal->date_render,
                'auteur'       => $journal->auteur_render,
                'action'       => $journal->action,
                'action_label' => $journal->action_render,
                'entite'       => $journal->entite_render,
                'entite_label' => $journal->entite_label,
                'ip'           => $journal->ip,
                'changements'  => JournalService::get_changements($journal),
            ],
        ]);
    }
}
