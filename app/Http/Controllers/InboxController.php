<?php

namespace App\Http\Controllers;

use App\Enums\Channels\ChannelProvider;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Channels\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class InboxController extends Controller
{
    public function __construct(
        protected ConversationService $conversations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                Conversation::STATUS_OPEN,
                Conversation::STATUS_PENDING,
                Conversation::STATUS_CLOSED,
            ])],
            'provider' => ['nullable', 'string', Rule::in(array_column(ChannelProvider::cases(), 'value'))],
            'unread' => ['nullable', Rule::in(['1'])],
        ]);

        $conversations = Conversation::query()
            ->with([
                'contact:id,display_name,phone,email,external_user_id',
                'lead:id,name,email,phone',
                'assignee:id,name',
                'connection:id,name',
            ])
            ->withMax('messages as latest_message_at', 'created_at')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($builder) use ($term) {
                    $builder->where('external_thread_id', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhereHas('contact', function ($contact) use ($term) {
                            $contact->where('display_name', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        })
                        ->orWhereHas('lead', function ($lead) use ($term) {
                            $lead->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['provider'] ?? null), fn ($q) => $q->where('provider', $filters['provider']))
            ->when(($filters['unread'] ?? null) === '1', fn ($q) => $q->where('unread_count', '>', 0))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('inbox.index', [
            'conversations' => $conversations,
            'filters' => $filters,
            'providers' => collect(ChannelProvider::cases())
                ->mapWithKeys(fn (ChannelProvider $provider) => [$provider->value => $provider->label()]),
            'statuses' => [
                Conversation::STATUS_OPEN => 'Open',
                Conversation::STATUS_PENDING => 'Pending',
                Conversation::STATUS_CLOSED => 'Closed',
            ],
        ]);
    }

    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $this->conversations->markRead($conversation);

        $conversation->load([
            'contact',
            'lead:id,name,email,phone,status',
            'assignee:id,name',
            'connection:id,name,provider,status,external_page_id',
            'messages' => fn ($q) => $q->with('user:id,name')->orderBy('id'),
        ]);

        $assignableUsers = User::query()
            ->where('company_id', $conversation->company_id)
            ->where('status', 'active')
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inbox.show', [
            'conversation' => $conversation->fresh(),
            'assignableUsers' => $assignableUsers,
            'canReply' => auth()->user()?->can('reply', $conversation) ?? false,
            'canAssign' => auth()->user()?->can('assign', $conversation) ?? false,
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('reply', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $this->conversations->reply($conversation, $request->user(), $validated['body']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Failed to send reply. Please try again.');
        }

        return redirect()
            ->route('inbox.show', $conversation)
            ->with('success', 'Reply sent.');
    }

    public function assign(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('assign', $conversation);

        $validated = $request->validate([
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('company_id', $conversation->company_id)
                    ->where('is_super_admin', false)),
            ],
        ]);

        $assignee = filled($validated['assigned_to'] ?? null)
            ? User::query()->find($validated['assigned_to'])
            : null;

        try {
            $this->conversations->assign($conversation, $assignee);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $assignee
            ? 'Conversation assigned to '.$assignee->name.'.'
            : 'Conversation unassigned.');
    }

    public function updateStatus(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('reply', $conversation);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Conversation::STATUS_OPEN,
                Conversation::STATUS_PENDING,
                Conversation::STATUS_CLOSED,
            ])],
        ]);

        try {
            $this->conversations->updateStatus($conversation, $validated['status']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Conversation marked as '.$validated['status'].'.');
    }
}
