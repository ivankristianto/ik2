// Callout.jsx — Note / Outdated / Updated.

function Callout({ variant = "note", title, children }) {
  const cls = "ik-callout ik-callout--" + variant;
  return (
    <aside className={cls}>
      {title ? <div className="ik-callout__title">{title}</div> : null}
      <div className="ik-callout__body">{children}</div>
    </aside>
  );
}

window.Callout = Callout;
