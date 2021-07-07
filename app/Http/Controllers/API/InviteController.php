<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Mpociot\Teamwork\Facades\Teamwork;
use Mpociot\Teamwork\TeamInvite;

class InviteController extends Controller {

    /**
     * List team invites
     */
    public function invites(Request $request) {
        return \App\Http\Resources\TeamInviteResource::collection( \Auth::user()->currentTeam->invites );
    }

    /**
     * @param Request $request
     * @param int $team_id
     * @return $this
     */
    public function invite(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);

        $team = \Auth::user()->currentTeam;

        if (!Teamwork::hasPendingInvite($request->email, $team)) {
            Teamwork::inviteToTeam($request->email, $team, function ($invite) {
                Mail::to($invite->email)
                    ->send(new \App\Mail\TeamInvite($invite));
            });
        } else {
            return response()->json([
                'errors' => [
                    'email' => 'The email address is already invited to the team.',
                ]
            ]);
        }

        return new \App\Http\Resources\TeamResource($team);
    }

    /**
     * Resend an invitation mail.
     *
     * @param $invite_id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function resendInvite($invite_id) {
        $invite = TeamInvite::findOrFail($invite_id);
        Mail::send('emails.invite', ['team' => $invite->team, 'invite' => $invite], function ($m) use ($invite) {
            $m->to($invite->email)->subject('Invitation to join team ' . $invite->team->name);
        });

        return new \App\Http\Resources\TeamResource($invite->team);
    }

    /**
     * Accept the given invite.
     * @param $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acceptInvite($token) {
        $invite = Teamwork::getInviteFromAcceptToken($token);

        if (!$invite) {
            abort(404);
        }

        if (auth()->check()) {
            Teamwork::acceptInvite($invite);
        } else {
            $user = \App\Models\User::create([
                'email' => $invite->email,
                'password' => bcrypt('secret')
            ]);
            
            $user->attachTeam($invite->team);

            // sending welcome message 
            Mail::send('emails.welcome', ['user' => $user, 'password' => 'secret'], function ($m) use ($user) {
                $m->to($user->email)->subject('Welcome, ' . $user->name);
            });
        }

        return response()->json([
            'message' => 'Successfully accepted'
        ]);
    }
 
}
