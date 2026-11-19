<?php

namespace Tests\Feature;

use App\Models\Balita;
use App\Models\Kader;
use App\Models\PengukuranPertumbuhan;
use App\Models\StandarAntropometri;
use App\Models\StatusGizi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BELUM DIJALANKAN saat ditulis (Composer/Packagist tidak terjangkau di lingkungan penulis).
 * Menggunakan factory/model PBI-1 milik tim: sesuaikan bagian setUp() dengan struktur
 * Balita, Kader, dan User di repo (kolom wajib, factory yang tersedia).
 * Data standar di sini adalah DATA UJI buatan (median 10, SD 1), bukan angka Permenkes.
 */
class PengukuranPertumbuhanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Balita $balita;

    protected function setUp(): void
    {
        parent::setUp();

        // --- SESUAIKAN dengan PBI-1 / seeder tim ---
        $this->user = User::factory()->create(['role' => 'kader']);
        Kader::factory()->create(['id_user' => $this->user->id]);
        $this->balita = Balita::factory()->create([
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => now()->subMonths(12)->toDateString(),
        ]);
        // -------------------------------------------

        foreach (['bb_u' => 12, 'pb_u' => 12, 'bb_pb' => 75.0] as $indikator => $acuan) {
            StandarAntropometri::create([
                'indikator' => $indikator, 'jenis_kelamin' => 'L', 'nilai_acuan' => $acuan,
                'sd_min3' => 7, 'sd_min2' => 8, 'sd_min1' => 9, 'median' => 10,
                'sd_plus1' => 11, 'sd_plus2' => 12, 'sd_plus3' => 13,
            ]);
        }
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'id_balita' => $this->balita->id_balita,
            'tanggal_ukur' => now()->toDateString(),
            'berat_badan' => 9.0,
            'tinggi_badan' => 75.0,
            'cara_ukur' => 'terlentang',
        ], $override);
    }

    public function test_kader_dapat_menyimpan_dan_status_gizi_terhitung(): void
    {
        $res = $this->actingAs($this->user)->post(route('pengukuran.store'), $this->payload());

        $pengukuran = PengukuranPertumbuhan::firstOrFail();
        $res->assertRedirect(route('pengukuran.show', $pengukuran));
        $this->assertSame(12, $pengukuran->usia_bulan);

        $status = StatusGizi::where('id_pengukuran', $pengukuran->id_pengukuran)->firstOrFail();
        $this->assertSame(-1.0, $status->zscore_bb_u);       // (9-10)/(10-9)
        $this->assertSame('Berat badan normal', $status->status_bb_u);
    }

    public function test_validasi_tanggal_masa_depan_dan_sebelum_lahir(): void
    {
        $this->actingAs($this->user)
            ->post(route('pengukuran.store'), $this->payload(['tanggal_ukur' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('tanggal_ukur');

        $this->actingAs($this->user)
            ->post(route('pengukuran.store'), $this->payload(['tanggal_ukur' => now()->subYears(5)->toDateString()]))
            ->assertSessionHasErrors('tanggal_ukur');

        $this->assertDatabaseCount('pengukuran_pertumbuhan', 0);
    }

    public function test_validasi_angka_di_luar_rentang(): void
    {
        $this->actingAs($this->user)
            ->post(route('pengukuran.store'), $this->payload(['berat_badan' => -1, 'tinggi_badan' => 500]))
            ->assertSessionHasErrors(['berat_badan', 'tinggi_badan']);
    }

    public function test_usia_di_atas_60_bulan_ditolak(): void
    {
        $tua = Balita::factory()->create([
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => now()->subMonths(70)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post(route('pengukuran.store'), $this->payload(['id_balita' => $tua->id_balita]))
            ->assertSessionHasErrors('tanggal_ukur');
    }

    public function test_ubah_menghitung_ulang_status_gizi(): void
    {
        $this->actingAs($this->user)->post(route('pengukuran.store'), $this->payload());
        $pengukuran = PengukuranPertumbuhan::firstOrFail();

        $this->actingAs($this->user)
            ->put(route('pengukuran.update', $pengukuran), $this->payload(['berat_badan' => 8.0]))
            ->assertRedirect();

        $this->assertSame(-2.0, StatusGizi::firstOrFail()->zscore_bb_u);
        $this->assertDatabaseCount('status_gizi', 1);
    }

    public function test_hapus_menghapus_status_gizi_terkait(): void
    {
        $this->actingAs($this->user)->post(route('pengukuran.store'), $this->payload());
        $pengukuran = PengukuranPertumbuhan::firstOrFail();

        $this->actingAs($this->user)->delete(route('pengukuran.destroy', $pengukuran));

        $this->assertDatabaseCount('pengukuran_pertumbuhan', 0);
        $this->assertDatabaseCount('status_gizi', 0);
    }

    public function test_orang_tua_tidak_punya_akses(): void
    {
        $ortu = User::factory()->create(['role' => 'orang_tua']);

        $this->actingAs($ortu)->get(route('pengukuran.index'))->assertForbidden();
        $this->actingAs($ortu)->post(route('pengukuran.store'), $this->payload())->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('pengukuran.index'))->assertRedirect(route('login'));
    }
}
