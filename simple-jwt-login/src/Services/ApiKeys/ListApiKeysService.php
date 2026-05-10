<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;

class ListApiKeysService extends BaseApiKeyService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireLoggedIn();

        $page    = max(1, (int) (isset($this->request['page']) ? $this->request['page'] : 1));
        $perPage = max(1, min(100, (int) (isset($this->request['per_page']) ? $this->request['per_page'] : 20)));
        $isAdmin = $this->wordPressData->currentUserCan('manage_options');

        $result = $isAdmin
            ? $this->apiKeyRepository->findAll($page, $perPage)
            : $this->apiKeyRepository->findByUserId(
                $this->wordPressData->getCurrentUserId(),
                $page,
                $perPage
            );

        $items = array_map(
            function ($item) use ($isAdmin) {
                $row = [
                    'id'           => (int) $item->id,
                    'name'         => $item->name,
                    'key_prefix'   => $item->key_prefix . '****',
                    'permissions'  => (array) json_decode($item->permissions, true),
                    'expires_at'   => $item->expires_at,
                    'last_used_at' => $item->last_used_at,
                    'created_at'   => $item->created_at,
                    'revoked_at'   => $item->revoked_at,
                ];
                if ($isAdmin) {
                    $row['user_id'] = (int) $item->user_id;
                }
                return $row;
            },
            (array) $result['items']
        );

        return $this->wordPressData->createResponse([
            'success' => true,
            'data'    => [
                'items'    => $items,
                'total'    => (int) $result['total'],
                'page'     => $page,
                'per_page' => $perPage,
            ],
        ]);
    }
}
