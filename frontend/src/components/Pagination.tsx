export function Pagination({
  page,
  lastPage,
  onPageChange,
}: {
  page: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}) {
  return (
    <div className="pagination">
      <button disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
        Previous
      </button>
      <span>Page {page} of {lastPage}</span>
      <button disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>
        Next
      </button>
    </div>
  );
}
