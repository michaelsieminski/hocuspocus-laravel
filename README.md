# Hocuspocus for Laravel Fork
Seamlessly integrates a [Hocuspocus](https://www.hocuspocus.dev) backend with Laravel.
Please note that the [original repository](https://github.com/ueberdosis/hocuspocus-laravel) is not well maintained by the Tiptap Team, which is why I decided
to work on my own version to use in my personal projects.

## Installation
You can install the package via composer:

```bash
composer require michaelsieminski/hocuspocus-laravel
```

Make sure to add the repository to your repositories in composer.json:

```bash
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/michaelsieminski/hocuspocus-laravel.git"
        }
    ]
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Hocuspocus\HocuspocusServiceProvider" --tag="hocuspocus-laravel-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Hocuspocus\HocuspocusServiceProvider" --tag="hocuspocus-laravel-config"
```

## Usage

Add the `CanCollaborate` trait to your user model:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Hocuspocus\Traits\CanCollaborate;

class User extends Authenticatable {
    use CanCollaborate;
}
```

Add the `Collaborative` interface and `IsCollaborative` trait to your documents and configure the `collaborativeAttributes`:

```php
use Illuminate\Database\Eloquent\Model;
use Hocuspocus\Contracts\Collaborative;
use Hocuspocus\Traits\IsCollaborative;

class TextDocument extends Model implements Collaborative {
    use IsCollaborative;

    protected array $collaborativeAttributes = [
        'title', 'body',
    ];
}
```

Add policies to your app that handle authorization for your models. The name of the policy method is configurable inside the `hocuspocus-laravel.php` config file. An example:

```php
use App\Models\TextDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TextDocumentPolicy
{
    use HandlesAuthorization;

    public function update(User $user, TextDocument $document)
    {
        return true;
    }
}
```

In the frontend, add the `collaborationAccessToken` and `collaborationDocumentName` to your WebSocket provider:

```blade
<script>
  window.collaborationAccessToken = '{{ optional(auth()->user())->getCollaborationAccessToken() }}';
  window.collaborationDocumentName = '{{ $yourTextDocument->getCollaborationDocumentName() }}'
</script>
```

```js
import { HocuspocusProvider } from '@hocuspocus/provider'
import * as Y from 'yjs'

const provider = new HocuspocusProvider({
  document: new Y.Doc(),
  url: 'ws://localhost:1234?access_token=' + window.collaborationAccessToken,
  name: window.collaborationDocumentName,
})
```

Configure a random secret key in your `.env`:

```dotenv
HOCUSPOCUS_SECRET="459824aaffa928e05f5b1caec411ae5f"
```

Finally set up Hocuspocus with the webhook & database extensions:

```js
import { Server } from '@hocuspocus/server'
import { Webhook, Events } from '@hocuspocus/extension-webhook'
import { TiptapTransformer } from '@hocuspocus/transformer'

const createSignature = (body: string): string => {
    const hmac = createHmac('sha256', '459824aaffa928e05f5b1caec411ae5f'); // secret from .env
    return `sha256=${hmac.update(body).digest('hex')}`;
};

const server = new Server({
  extensions: [
    new Database({
        fetch: async ({ documentName, requestParameters }) => {
            try {
                const contentToSign = JSON.stringify({
                    documentName: documentName,
                    queryParams: requestParameters,
                });

                const response = await axios.get(`https://example.com/api/documents/get/${encodeURIComponent(documentName)}`, {
                    params: requestParameters,
                    headers: {
                        Accept: 'application/json',
                        'X-Hocuspocus-Signature-256': createSignature(contentToSign),
                    },
                });

                if (response.data && response.data.data) {
                    if (response.data.data.length > 0) {
                        return new Uint8Array(response.data.data);
                    }
                }

                const ydoc = TiptapTransformer.toYdoc(
                    {
                        type: 'doc',
                        content: [{ type: 'paragraph' }],
                    },
                    'description',
                );
                return Y.encodeStateAsUpdate(ydoc);
            } catch (error) {
                console.error(error)
                const ydoc = TiptapTransformer.toYdoc(
                    {
                        type: 'doc',
                        content: [{ type: 'paragraph' }],
                    },
                    'description',
                );
                return Y.encodeStateAsUpdate(ydoc);
            }
        },
        store: async ({ document, documentName, requestHeaders, requestParameters, context, state }) => {
            const json = JSON.stringify({
                payload: {
                    document: TiptapTransformer.fromYdoc(document),
                    documentName,
                    context,
                    requestHeaders,
                    requestParameters: Object.fromEntries(requestParameters.entries()),
                    state,
                },
            });

            return axios.post('https://example.com/api/documents/store', json, {
                headers: {
                    'X-Hocuspocus-Signature-256': createSignature(json),
                    'Content-Type': 'application/json',
                },
            });
        },
      }),
    new Webhook({
      // url to your application
      url: 'https://example.com/api/documents',
      // the same secret you configured earlier in your .env
      secret: '459824aaffa928e05f5b1caec411ae5f',

      transformer: TiptapTransformer,
    }),
  ],
})

server.listen()
```

## Credits
- [Ueberdosis](https://github.com/ueberdosis)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
