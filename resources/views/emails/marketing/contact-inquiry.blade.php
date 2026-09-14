New contact inquiry from the NexaCRM marketing website

Name: {{ $inquiry['name'] }}
Email: {{ $inquiry['email'] }}
Company: {{ $inquiry['company'] ?: '—' }}
Phone: {{ $inquiry['phone'] ?: '—' }}
Intent: {{ $inquiry['intent'] ?: 'general' }}

Message:
{{ $inquiry['message'] }}
