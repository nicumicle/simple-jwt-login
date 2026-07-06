<?php

namespace SimpleJWTLogin\Services\RevokedTokens;

use Exception;

class ListRevokedTokensService extends BaseRevokedTokenService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireAdmin();

        $page    = max(1, (int) (isset($this->request['page']) ? $this->request['page'] : 1));
        $perPage = max(1, min(100, (int) (isset($this->request['per_page']) ? $this->request['per_page'] : 20)));

        $result = $this->revokedTokenRepo->findAll($page, $perPage);

        $items = array_map(
            function ($item) {
                return [
                    'id'                => (int) $item->id,
                    'user_id'           => (int) $item->user_id,
                    'token_hash_masked' => substr($item->token_hash, 0, 8) . '...' . substr($item->token_hash, -4),
                    'expires_at'        => $item->expires_at,
                    'revoked_at'        => $item->revoked_at,
                ];
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
