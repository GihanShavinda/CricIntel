export class EventDeduplicator {
  private ids =
    new Set<string>();

  private order:
    string[] = [];

  public constructor(
    private readonly limit = 250
  ) {}

  public accept(
    eventId: string
  ): boolean {
    if (
      this.ids.has(
        eventId
      )
    ) {
      return false;
    }

    this.ids.add(
      eventId
    );

    this.order.push(
      eventId
    );

    if (
      this.order.length >
      this.limit
    ) {
      const oldest =
        this.order.shift();

      if (oldest) {
        this.ids.delete(
          oldest
        );
      }
    }

    return true;
  }

  public clear(): void {
    this.ids.clear();
    this.order = [];
  }
}
