<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Quello che i consulenti sanno di te.
 *
 * Il prompt è impersonale di proposito: le cose vere di una persona sola —
 * gli obiettivi, gli attrezzi che ha in garage, com'è fatta la sua entrata —
 * scritte nel codice diventano un prompt per ogni utente, cioè ogni regola di
 * dominio tenuta allineata in due copie. Qui invece stanno in una riga di
 * database, e ognuno ha le sue.
 *
 * Tre campi e non uno perché le due conversazioni non si vedono fra loro. Il
 * consulente delle spese non ha motivo di sapere quanto pesi, e quello della
 * salute non ha motivo di sapere quanto guadagni: mescolarli qui annullerebbe
 * quella divisione e la farebbe pagare in token a ogni domanda.
 */
class AssistantContext extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Cosa sanno di te';

    protected static ?string $title = 'Cosa sanno di te i consulenti';

    protected string $view = 'filament.pages.assistant-context';

    /**
     * Un tetto, perché ogni carattere qui dentro si paga a ogni domanda.
     *
     * Questo testo sta nel blocco variabile del prompt: non entra in cache, e
     * viene rispedito per intero a ogni turno. Duemila caratteri sono
     * abbondanti per degli obiettivi e una lista di attrezzi, e sono ~500
     * token — un incollaggio di dieci pagine invece si sentirebbe sul conto
     * senza che nessuno colleghi le due cose.
     */
    public const MAX = 2000;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->only(['assistant_notes', 'health_notes', 'finance_notes']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vale per tutti e due')
                    ->description('Chi sei, cosa fai, come preferisci che ti si parli. Lo leggono sia il consulente salute sia quello spese.')
                    ->schema([
                        Textarea::make('assistant_notes')
                            ->hiddenLabel()
                            ->rows(4)
                            ->maxLength(self::MAX)
                            ->placeholder('Lavoro in ufficio, sveglia alle 6. Preferisco risposte brevi e senza giri di parole.'),
                    ]),

                Section::make('Solo il consulente salute')
                    ->description('Obiettivi di peso e di fisico, attrezzi che hai a disposizione, infortuni da rispettare, come mangi di solito.')
                    ->schema([
                        Textarea::make('health_notes')
                            ->hiddenLabel()
                            ->rows(6)
                            ->maxLength(self::MAX)
                            ->placeholder("Obiettivo: scendere a 78 kg entro dicembre, senza perdere forza.\nIn garage: bilanciere, dischi fino a 100 kg, panca regolabile, cyclette.\nSpalla destra da rispettare: niente military press sopra la testa.")
                            // Era nella pagina del profilo, e ci stava stretta:
                            // lì sono i numeri che servono al calcolo, qui è
                            // quello che serve a dare un consiglio.
                            ->helperText('Il consulente le legge a ogni domanda, e ci basa i consigli e le schede che ti propone.'),
                    ]),

                Section::make('Solo il consulente spese')
                    ->description('Come sono fatte le tue entrate, cosa stai mettendo da parte, che spese consideri normali e quali no.')
                    ->schema([
                        Textarea::make('finance_notes')
                            ->hiddenLabel()
                            ->rows(6)
                            ->maxLength(self::MAX)
                            ->placeholder("Stipendio fisso il 27, più fatture saltuarie da partita IVA.\nObiettivo: mettere via 500 € al mese.\nL'affitto e le bollette sono spese fisse, non c'è niente da ottimizzare lì."),
                    ]),
            ])
            ->statePath('data');
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('salva')->label('Salva')->submit('save'),
        ];
    }

    public function save(): void
    {
        Auth::user()->update($this->form->getState());

        Notification::make()
            ->title('Contesto aggiornato')
            ->body('I consulenti lo leggono dalla prossima domanda.')
            ->success()
            ->send();
    }
}
