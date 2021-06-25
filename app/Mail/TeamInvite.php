<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamInvite extends Mailable {

    use Queueable, SerializesModels;

    protected $teamInvite;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($teamInvite) {
        $this->teamInvite = $teamInvite;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->view('emails.invite')
            ->subject('Invitation to join workspace ' . $this->teamInvite->team->name)
            ->with(['team' => $this->teamInvite->team, 'invite' => $this->teamInvite]);
    }
}
