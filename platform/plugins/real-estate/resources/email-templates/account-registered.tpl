{{ header }}

<p>{{ 'plugins/real-estate::real-estate.email_templates.greeting_admin' | trans }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.account_registered_message' | trans }}</p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.field_name' | trans }} <strong>{{ account_name }}</strong></p>
<p>{{ 'plugins/real-estate::real-estate.email_templates.field_email' | trans }} <strong>{{ account_email }}</strong></p>

{{ footer }}
