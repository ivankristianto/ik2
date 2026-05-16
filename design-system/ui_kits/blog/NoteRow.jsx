// NoteRow.jsx — two-column date + linked title row used in archives & changelog.

function NoteRow({ note, onOpen = () => {} }) {
  return (
    <div className="ik-note-row">
      <span className="ik-note-row__date">{note.date}</span>
      <div className="ik-note-row__body">
        <a className="ik-note-row__title" href="#" onClick={(e) => { e.preventDefault(); onOpen(note); }}>
          {note.title}
        </a>
        <div className="ik-note-row__tags">
          {note.tags.join(" · ")}
        </div>
      </div>
    </div>
  );
}

window.NoteRow = NoteRow;
