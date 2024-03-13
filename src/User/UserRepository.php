<?php

namespace LuckPermsAPI\User;

use Illuminate\Support\Collection;
use LuckPermsAPI\Exception\UserNotFoundException;
use LuckPermsAPI\Repository\Repository;
use LuckPermsAPI\Repository\Search;

class UserRepository extends Repository
{
    public function allIdentifiers(): Collection
    {
        if (isset($this->identifiers)) {
            return $this->identifiers;
        }

        $this->identifiers = new Collection();

        $response = $this->session->httpClient->get('/user');

        return $this->identifiers = collect($this->json($response->getBody()->getContents()));
    }

    public function search(Search $search): Collection
    {
        $response = $this->session->httpClient->get('/user/search', [
            'query' => $search->toArray(),
        ]);

        $userMapper = resolve(UserMapper::class);

        return collect($this->json($response->getBody()->getContents()))->map(function ($userData) {
            return [
                'uniqueId' => $userData['uniqueId'],
                'results' => $userData['results'],
            ];
        });
    }

    public function load(string $identifier): User
    {
        if ($this->objects->has($identifier)) {
            return $this->objects->get($identifier);
        }

        $response = $this->session->httpClient->get("/user/{$identifier}");

        if ($response->getStatusCode() === 404) {
            throw new UserNotFoundException("User with identifier '{$identifier}' not found");
        }

        $data = $this->json($response->getBody()->getContents());

        $user = resolve(UserMapper::class)->map($data);

        $this->objects->put($identifier, $user);

        return $user;
    }
}
