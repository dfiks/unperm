<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Traits;

trait HasBitmask
{
    public function hasBit(int $bit): bool
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $checkBit = gmp_pow(2, $bit);
        return gmp_cmp(gmp_and($bitmask, $checkBit), 0) !== 0;
    }

    public function setBit(int $bit): self
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $setBit = gmp_pow(2, $bit);
        $this->bitmask = gmp_strval(gmp_or($bitmask, $setBit));
        return $this;
    }

    public function unsetBit(int $bit): self
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $unsetBit = gmp_pow(2, $bit);
        $this->bitmask = gmp_strval(gmp_and($bitmask, gmp_com($unsetBit)));
        return $this;
    }

    public function toggleBit(int $bit): self
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $toggleBit = gmp_pow(2, $bit);
        $this->bitmask = gmp_strval(gmp_xor($bitmask, $toggleBit));
        return $this;
    }

    public function hasAnyBits(string|int $mask): bool
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $checkMask = gmp_init((string)$mask);
        return gmp_cmp(gmp_and($bitmask, $checkMask), 0) !== 0;
    }

    public function hasAllBits(string|int $mask): bool
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $checkMask = gmp_init((string)$mask);
        return gmp_cmp(gmp_and($bitmask, $checkMask), $checkMask) === 0;
    }

    public function setBits(string|int $mask): self
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $setMask = gmp_init((string)$mask);
        $this->bitmask = gmp_strval(gmp_or($bitmask, $setMask));
        return $this;
    }

    public function unsetBits(string|int $mask): self
    {
        $bitmask = gmp_init($this->bitmask ?? '0');
        $unsetMask = gmp_init((string)$mask);
        $this->bitmask = gmp_strval(gmp_and($bitmask, gmp_com($unsetMask)));
        return $this;
    }

    public function clearBitmask(): self
    {
        $this->bitmask = '0';
        return $this;
    }
}
