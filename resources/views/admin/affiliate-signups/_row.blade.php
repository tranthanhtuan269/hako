<tr class="affiliate-signup-row{{ ! empty($project['is_registered']) ? ' is-registered' : '' }}"
    data-project-id="{{ (int) $project['id'] }}">
    <td>
        <strong>{{ $project['project'] ?: '—' }}</strong>
        @if(! empty($project['is_registered']))
            <span class="badge badge-success affiliate-signup-status">Đã đăng ký</span>
        @endif
    </td>
    <td>
        @if(! empty($project['domain_link']))
            <a href="{{ $project['domain_link'] }}" target="_blank" rel="noopener">{{ $project['domain_link'] }}</a>
        @else
            —
        @endif
    </td>
    <td class="small">
        <a href="{{ $project['signup_link'] }}" target="_blank" rel="noopener" class="affiliate-signup-link">{{ $project['signup_link'] }}</a>
    </td>
    <td>{{ $project['category'] ?: '—' }}</td>
    <td class="small text-muted affiliate-signup-date">
        @if(! empty($project['is_registered']) && ! empty($project['registered_at']))
            {{ $project['registered_at'] }}
        @else
            {{ $project['created_at'] ?: '—' }}
        @endif
    </td>
    <td>
        @if(! empty($project['is_registered']))
            <button type="button" class="btn btn-outline btn-sm" disabled>Đã đăng ký</button>
        @else
            <button type="button"
                class="btn btn-primary btn-sm js-affiliate-signup-register"
                data-id="{{ (int) $project['id'] }}"
                data-project="{{ $project['project'] ?? '' }}"
                data-signup-url="{{ $project['signup_link'] }}"
                data-register-url="{{ route('admin.affiliate-signups.register', (int) $project['id']) }}">
                Đăng ký
            </button>
        @endif
    </td>
</tr>
