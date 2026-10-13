@props(['id', 'url', 'method' => 'GET', 'payload' => []])
@php
    $snippets = [
        'curl' => "curl -sS \\\n  -H \"Authorization: Bearer TOKEN_ANDA\" \\\n  -H \"Accept: application/json\" \\\n  \"{$url}\"",
        'Python' => "import json\nfrom urllib.request import Request, urlopen\n\nrequest = Request(\n    \"{$url}\",\n    headers={\n        \"Authorization\": \"Bearer TOKEN_ANDA\",\n        \"Accept\": \"application/json\",\n    },\n)\nwith urlopen(request) as response:\n    data = json.load(response)\nprint(data)",
        'JavaScript' => "const response = await fetch(\n  \"{$url}\",\n  { headers: {\n    Authorization: \"Bearer TOKEN_ANDA\",\n    Accept: \"application/json\",\n  } },\n);\nif (!response.ok) throw new Error(`HTTP \${response.status}`);\nconst data = await response.json();\nconsole.log(data);",
        'PHP' => "\$request = curl_init(\"{$url}\");\ncurl_setopt_array(\$request, [\n    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_HTTPHEADER => [\n        'Authorization: Bearer TOKEN_ANDA',\n        'Accept: application/json',\n    ],\n]);\n\$response = curl_exec(\$request);\nif (\$response === false) {\n    throw new RuntimeException(curl_error(\$request));\n}\n\$status = curl_getinfo(\$request, CURLINFO_HTTP_CODE);\ncurl_close(\$request);\nif (\$status >= 400) {\n    throw new RuntimeException('HTTP '.\$status);\n}\n\$data = json_decode(\$response, true, 512, JSON_THROW_ON_ERROR);\nprint_r(\$data);",
    ];
    if ($method === 'POST') {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $snippets['curl'] = "curl -sS -X POST \\\n  -H \"Authorization: Bearer TOKEN_ANDA\" \\\n  -H \"Accept: application/json\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{$json}' \\\n  \"{$url}\"";
        $snippets['Python'] = "import json\nfrom urllib.request import Request, urlopen\n\npayload = json.loads('{$json}')\nrequest = Request(\n    \"{$url}\",\n    data=json.dumps(payload).encode(\"utf-8\"),\n    headers={\n        \"Authorization\": \"Bearer TOKEN_ANDA\",\n        \"Accept\": \"application/json\",\n        \"Content-Type\": \"application/json\",\n    },\n    method=\"POST\",\n)\nwith urlopen(request) as response:\n    data = json.load(response)\nprint(data)";
        $snippets['JavaScript'] = "const response = await fetch(\n  \"{$url}\",\n  {\n    method: \"POST\",\n    headers: {\n      Authorization: \"Bearer TOKEN_ANDA\",\n      Accept: \"application/json\",\n      \"Content-Type\": \"application/json\",\n    },\n    body: JSON.stringify({$json}),\n  },\n);\nif (!response.ok) throw new Error(`HTTP \${response.status}`);\nconst data = await response.json();\nconsole.log(data);";
        $snippets['PHP'] = str_replace(
            ["    CURLOPT_RETURNTRANSFER => true,", "        'Accept: application/json',"],
            ["    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_POST => true,\n    CURLOPT_POSTFIELDS => '{$json}',", "        'Accept: application/json',\n        'Content-Type: application/json',"],
            $snippets['PHP'],
        );
    }
@endphp
<div class="mt-4 overflow-hidden rounded-xl border border-slate-700 bg-slate-950 text-slate-100" data-api-example>
    <div class="flex items-center gap-2 border-b border-slate-700 px-3 py-2">
        <div class="flex min-w-0 flex-1 gap-1 overflow-x-auto" role="tablist" aria-label="Bahasa contoh request API">
            @foreach ($snippets as $language => $snippet)
                <button class="api-example-tab" type="button" role="tab" id="{{ $id }}-tab-{{ $loop->index }}" aria-controls="{{ $id }}-panel-{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-example-tab>{{ $language }}</button>
            @endforeach
        </div>
        <button class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-400" type="button" data-example-copy>Salin kode</button>
    </div>
    @foreach ($snippets as $language => $snippet)
        <pre class="overflow-x-auto p-4 text-xs leading-6 text-sky-200" role="tabpanel" id="{{ $id }}-panel-{{ $loop->index }}" aria-labelledby="{{ $id }}-tab-{{ $loop->index }}" tabindex="0" data-example-panel @if (! $loop->first) hidden @endif><code>{{ $snippet }}</code></pre>
    @endforeach
    <p class="px-4 text-xs text-slate-300" role="status" data-example-status></p>
</div>
