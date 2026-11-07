<?php

namespace Tests\Unit;

use App\Support\MenuGroup;
use PHPUnit\Framework\TestCase;

class MenuGroupTest extends TestCase
{
    public function test_kategori_dikelompokkan_untuk_filter_publik(): void
    {
        $this->assertSame(MenuGroup::KOPI, MenuGroup::forSlug('kopi'));
        $this->assertSame(MenuGroup::BUKAN_KOPI, MenuGroup::forSlug('bukan-kopi'));
        $this->assertSame(MenuGroup::BUKAN_KOPI, MenuGroup::forSlug('teh'));
        $this->assertSame(MenuGroup::BUKAN_KOPI, MenuGroup::forSlug('autentik'));
        $this->assertSame(MenuGroup::MAKANAN_CAMILAN, MenuGroup::forSlug('makanan-camilan'));
        $this->assertSame(MenuGroup::MAKANAN_CAMILAN, MenuGroup::forSlug('makanan'));
        $this->assertSame(MenuGroup::MAKANAN_CAMILAN, MenuGroup::forSlug('cemilan'));
    }

    public function test_kategori_tak_dikenal_hanya_masuk_semua(): void
    {
        $this->assertSame(MenuGroup::LAINNYA, MenuGroup::forSlug('kategori-baru'));
        $this->assertSame(MenuGroup::LAINNYA, MenuGroup::forSlug(null));
    }

    public function test_tiga_kelompok_publik_tersedia(): void
    {
        $groups = MenuGroup::all();

        $this->assertCount(3, $groups);
        $this->assertSame(
            ['kopi', 'bukan-kopi', 'makanan-camilan'],
            array_column($groups, 'slug')
        );
        $this->assertSame(
            ['Kopi', 'Bukan Kopi', 'Makanan & Camilan'],
            array_column($groups, 'label')
        );
    }
}
