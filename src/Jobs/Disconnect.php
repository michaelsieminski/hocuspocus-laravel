<?php


namespace Hocuspocus\Jobs;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Hocuspocus\Contracts\Collaborative;

class Disconnect implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected Authenticatable $user;

    protected Collaborative $document;

    public function __construct(Authenticatable $user, Collaborative $document)
    {
        $this->user = $user;
        $this->document = $document;
    }

    public function handle()
    {
        $this->user->collaborator->disconnectFrom($this->document);
    }
}
