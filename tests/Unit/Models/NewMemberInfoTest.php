<?php

namespace Tests\Unit\Models;

use App\Models\NewMemberInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewMemberInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_new_member_info(): void
    {
        $info = NewMemberInfo::factory()->create();
        $this->assertDatabaseHas('new_member_info', ['id' => $info->id]);
    }

    public function test_active_state(): void
    {
        $info = NewMemberInfo::factory()->active()->create();
        $this->assertTrue($info->is_active);
    }

    public function test_inactive_state(): void
    {
        $info = NewMemberInfo::factory()->inactive()->create();
        $this->assertFalse($info->is_active);
    }

    public function test_general_category_state(): void
    {
        $info = NewMemberInfo::factory()->general()->create();
        $this->assertEquals('general', $info->category);
    }

    public function test_rules_category_state(): void
    {
        $info = NewMemberInfo::factory()->rules()->create();
        $this->assertEquals('rules', $info->category);
    }

    public function test_faq_category_state(): void
    {
        $info = NewMemberInfo::factory()->faq()->create();
        $this->assertEquals('faq', $info->category);
    }

    public function test_category_is_valid(): void
    {
        $info = NewMemberInfo::factory()->create();
        $validCategories = ['general', 'rules', 'benefits', 'schedule', 'equipment', 'faq'];
        $this->assertContains($info->category, $validCategories);
    }

    public function test_has_title_and_content(): void
    {
        $info = NewMemberInfo::factory()->create();
        $this->assertNotEmpty($info->title);
        $this->assertNotEmpty($info->content);
    }

    public function test_category_label_attribute(): void
    {
        $info = NewMemberInfo::factory()->general()->create();
        $this->assertEquals('Γενικές Πληροφορίες', $info->category_label);
    }

    public function test_active_scope(): void
    {
        NewMemberInfo::factory()->active()->count(2)->create();
        NewMemberInfo::factory()->inactive()->create();

        $this->assertCount(2, NewMemberInfo::active()->get());
    }
}
