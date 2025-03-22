<?php


namespace Hocuspocus\Jobs;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Hocuspocus\Contracts\Collaborative;

class Store implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected Authenticatable $user;

    protected Collaborative $document;

    protected string $encodedDataString;

    public function __construct(Authenticatable $user, Collaborative $document, string $binaryDataString)
    {
        $this->user = $user;
        $this->document = $document;
        $this->encodedDataString = base64_encode($binaryDataString);
    }

    public function handle()
    {
        $binaryDataString = base64_decode($this->encodedDataString);

        $this->user->collaborator->updateDocumentData($this->document, $binaryDataString);
    }
}
