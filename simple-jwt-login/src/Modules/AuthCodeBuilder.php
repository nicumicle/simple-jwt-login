<?php

namespace SimpleJWTLogin\Modules;

class AuthCodeBuilder
{
    /**
     * @var array|mixed|string
     */
    private $code;
    /**
     * @var mixed|string|null
     */
    private $role;
    /**
     * @var mixed|string|null
     */
    private $expirationDate;


    /**
     * AuthCodeBuilder constructor.
     *
     * @param string|array$data
     */
    public function __construct($data)
    {
        $newMode = is_array($data) && isset($data['code']) && isset($data['role']) && isset($data['expiration_date']);

        $this->code = $newMode
            ? $data['code']
            : (string) $data;
        $this->role = $newMode
            ? $data['role']
            : '';
        $this->expirationDate = $newMode
            ? $data['expiration_date']
            : '';
    }

    /**
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string|null
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'role' => $this->role,
            'expiration_date' => $this->expirationDate
        ];
    }
}
