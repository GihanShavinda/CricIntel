import { describe, expect, it } from "vitest";

import { EventDeduplicator } from "./eventDeduplicator";

describe("EventDeduplicator", () => {
  it("accepts an event only once", () => {
    const deduplicator = new EventDeduplicator();

    expect(deduplicator.accept("event-1")).toBe(true);

    expect(deduplicator.accept("event-1")).toBe(false);
  });

  it("forgets oldest ids when bounded limit is exceeded", () => {
    const deduplicator = new EventDeduplicator(2);

    expect(deduplicator.accept("a")).toBe(true);

    expect(deduplicator.accept("b")).toBe(true);

    expect(deduplicator.accept("c")).toBe(true);

    expect(deduplicator.accept("a")).toBe(true);
  });
});
