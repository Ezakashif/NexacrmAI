New contact inquiry from the Algos marketing website

Name: {{ $inquiry['name'] }}
Email: {{ $inquiry['email'] }}
Company: {{ $inquiry['company'] ?: '—' }}
Phone: {{ $inquiry['phone'] ?: '—' }}
Intent: {{ $inquiry['intent'] ?: 'general' }}

Message:
{{ $inquiry['message'] }}
