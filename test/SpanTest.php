<?php

class SpanTest extends PHPUnit_Framework_TestCase {

    public function testSpanSetOperation() {
        $runtime = LightStep::newTracer("test_group", "1234567890");
        $span = $runtime->startSpan("server/query");
        $span->finish();

        $this->assertEquals(peek($span, "_operation"), "server/query");
    }

    public function testSpanStartEndMicros() {
        $runtime = LightStep::newTracer("test_group", "1234567890");

        $sum = 0;
        for ($i = 0; $i < 50; $i++) {
            $span = $runtime->startSpan("start_end");
            usleep(500);
            $span->finish();

            $start = peek($span, "_startMicros");
            $end = peek($span, "_endMicros");
            $delta = $end - $start;
            $sum += $delta;

            $this->assertGreaterThan(0, $delta);
        }
        $avg = $sum / 50;

        // Is the average at least *reasonable*? We're not demanding
        // a lot of precision from usleep() here.
        $this->assertGreaterThan(100, $avg);
        $this->assertLessThan(1000, $avg);
    }

    public function testSpanJoinIds() {
        $runtime = LightStep::newTracer("test_group", "1234567890");
        $span = $runtime->startSpan("join_id_span");

        $span->addTraceJoinId("number", "one");
        $this->assertEquals(count(peek($span, "_joinIds")), 1);

        $span->setEndUserId("mr_jones");
        $this->assertEquals(count(peek($span, "_joinIds")), 2);
    }

    public function testSpanLogging() {
        $runtime = LightStep::newTracer("test_group", "1234567890");
        $span = $runtime->startSpan("log_span");
        $span->infof("Test %d %f %s", 1, 2.0, "three");
        $span->warnf("Test %d %f %s", 1, 2.0, "three");
        $span->errorf("Test %d %f %s", 1, 2.0, "three");
        $span->finish();
    }

    public function testSpanAttributes() {
        $runtime = LightStep::newTracer("test_group", "1234567890");
        $span = $runtime->startSpan("attributes_span");
        $span->setTag("test_attribute_1", "value 1");
        $span->setTag("test_attribute_2", "value 2");

        $this->assertEquals(count(peek($span, "_tags")), 2);

        $span->setTag("test_attribute_3", "value 3");

        $this->assertEquals(count(peek($span, "_tags")), 3);

        $span->finish();
    }

    public function testSpanThriftRecord() {
        $runtime = LightStep::newTracer("test_group", "1234567890");
        $span = $runtime->startSpan("hello/world");
        $span->setEnduserId("dinosaur_sr");
        $span->finish();

        // Transform the object into a associative array
        $arr = json_decode(json_encode($span->toThrift()), TRUE);
        $this->assertTrue(is_string($arr["span_guid"]));
        $this->assertTrue(is_string($arr["runtime_guid"]));
        $this->assertTrue(is_string($arr["span_name"]));
        $this->assertEquals(1, count($arr["join_ids"]));
        $this->assertTrue(is_string($arr["join_ids"][0]["TraceKey"]));
        $this->assertTrue(is_string($arr["join_ids"][0]["Value"]));
    }
}
