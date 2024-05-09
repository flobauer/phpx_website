<?php

namespace App\Actions;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class RsvpToMeetup
{
	use AsAction;
	
	public static function routes(Router $router): void
	{
		$router->post('meetups/{meetup}/rsvps', static::class);
	}
	
	public function handle(Meetup $meetup, User $user): void
	{
		$meetup->users()->syncWithoutDetaching($user->getKey());
	}
	
	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'email', 'max:255'],
			'subscribe' => ['nullable', 'boolean'],
			// 'interests' => ['array'],
		];
	}
	
	public function asController(ActionRequest $request, Meetup $meetup)
	{
		$user = JoinGroup::run(
			group: $meetup->group,
			name: $request->validated('name'),
			email: $request->validated('email'),
			subscribe: $request->boolean('subscribe'),
		);
		
		$this->handle($meetup, $user);
		
		return redirect()->back();
	}
	
	public function getCommandSignature(): string
	{
		return 'meetup:rsvp {meetup} {name} {email} {--subscribe}';
	}
	
	public function asCommand(Command $command): int
	{
		$meetup = Meetup::findOrFail($command->argument('meetup'));
		
		$user = JoinGroup::run(
			group: $meetup->group,
			name: $command->argument('name'),
			email: $command->argument('email'),
			subscribe: $command->option('subscribe'),
		);
		
		$this->handle($meetup, $user);
		
		$command->info('User is now RSVP’d.');
		return 0;
	}
}
