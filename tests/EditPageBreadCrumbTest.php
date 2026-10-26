<?php

use App\Models\Camp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Testing\DatabaseTransactions;

class EditPageBreadCrumbTest extends TestCase
{
    use DatabaseTransactions;

    private $topicNum = 990001;

    /**
     * Topic 990001 tree: Agreement(1) -> Parent(2) -> Child(3), plus a not yet live camp 4 under camp 2.
     * Camp 3 also has a pending revision that renames it and moves it under Agreement.
     */
    public function setUp(): void
    {
        parent::setUp();
        $past = time() - 3600;
        $future = time() + 3600;

        DB::table('topic')->insert([
            'topic_num' => $this->topicNum, 'topic_name' => 'Breadcrumb Topic', 'namespace_id' => 1, 'note' => '',
            'submitter_nick_id' => 1, 'submit_time' => $past, 'go_live_time' => $past,
        ]);

        $this->insertCamp(1, null, 'Agreement', $past, $past);
        $this->insertCamp(2, 1, 'Parent Camp', $past, $past);
        $this->insertCamp(3, 2, 'Child Camp', $past, $past);
        $this->insertCamp(4, 2, 'Proposed Camp', time(), $future);

        DB::table('statement')->insert([
            'topic_num' => $this->topicNum, 'camp_num' => 3, 'value' => 'statement', 'parsed_value' => 'statement', 'note' => '',
            'submitter_nick_id' => 1, 'submit_time' => $past, 'go_live_time' => $past,
        ]);
    }

    private function insertCamp($campNum, $parentCampNum, $campName, $submitTime, $goLiveTime)
    {
        return DB::table('camp')->insertGetId([
            'topic_num' => $this->topicNum, 'camp_num' => $campNum, 'parent_camp_num' => $parentCampNum,
            'title' => $campName, 'camp_name' => $campName, 'note' => '', 'submitter_nick_id' => 1,
            'submit_time' => $submitTime, 'go_live_time' => $goLiveTime,
        ]);
    }

    private function campNums(array $breadCrumb): array
    {
        return array_map(function ($item) {
            return $item['camp_num'];
        }, $breadCrumb);
    }

    public function testBreadCrumbIncludesFullHierarchyWithCampLinks()
    {
        $breadCrumb = Camp::getEditPageBreadCrumb($this->topicNum, 3);

        $this->assertEquals([1, 2, 3], $this->campNums($breadCrumb));
        $this->assertEquals('Agreement', $breadCrumb[0]['camp_name']);
        foreach ($breadCrumb as $item) {
            $this->assertEquals($this->topicNum, $item['topic_num']);
            $this->assertStringContainsString('/topic/' . $this->topicNum . '-', $item['camp_link']);
            $this->assertStringEndsWith('/' . $item['camp_num'] . '-' . str_replace(' ', '-', $item['camp_name']), $item['camp_link']);
        }
    }

    public function testBreadCrumbForTopicIsAgreementOnly()
    {
        $this->assertEquals([1], $this->campNums(Camp::getEditPageBreadCrumb($this->topicNum)));
    }

    public function testBreadCrumbForNotLiveCampFallsBackToLatestRevision()
    {
        $this->assertEquals([1, 2, 4], $this->campNums(Camp::getEditPageBreadCrumb($this->topicNum, 4)));
    }

    public function testBreadCrumbIsEmptyForUnknownCamp()
    {
        $this->assertEquals([], Camp::getEditPageBreadCrumb($this->topicNum, 99));
    }

    public function testEditCampReturnsLiveBreadCrumbForPendingRevision()
    {
        $recordId = $this->insertCamp(3, 1, 'Renamed Child', time(), time() + 3600);
        $user = User::factory()->make();

        $this->actingAs($user)->post('/api/v3/edit-camp', ['record_id' => $recordId, 'event_type' => 'edit']);

        $this->assertEquals(200, $this->response->status());
        $breadCrumb = json_decode($this->response->getContent(), true)['data']['bread_crumb'];
        $this->assertEquals([1, 2, 3], $this->campNums($breadCrumb));
        $this->assertEquals('Child Camp', $breadCrumb[2]['camp_name']);
    }

    public function testEditTopicReturnsBreadCrumb()
    {
        $recordId = DB::table('topic')->where('topic_num', $this->topicNum)->value('id');
        $user = User::factory()->make();

        $this->actingAs($user)->post('/api/v3/edit-topic', ['record_id' => $recordId, 'event_type' => 'edit']);

        $this->assertEquals(200, $this->response->status());
        $breadCrumb = json_decode($this->response->getContent(), true)['data']['bread_crumb'];
        $this->assertEquals([1], $this->campNums($breadCrumb));
    }

    public function testEditTopicWithUnknownRecordReturns404()
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/api/v3/edit-topic', ['record_id' => 0, 'event_type' => 'edit']);

        $this->assertEquals(404, $this->response->status());
    }

    public function testEditStatementReturnsBreadCrumb()
    {
        $recordId = DB::table('statement')->where('topic_num', $this->topicNum)->value('id');
        $user = User::factory()->make();

        $this->actingAs($user)->post('/api/v3/edit-camp-statement', ['record_id' => $recordId, 'event_type' => 'edit']);

        $this->assertEquals(200, $this->response->status());
        $breadCrumb = json_decode($this->response->getContent(), true)['data']['bread_crumb'];
        $this->assertEquals([1, 2, 3], $this->campNums($breadCrumb));
    }
}
