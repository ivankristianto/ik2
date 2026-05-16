// GuideCard.jsx — eyebrow + tight title + graphite description.

function GuideCard({ guide, onOpen = () => {} }) {
  return (
    <article className="ik-guide-card" onClick={() => onOpen(guide)}>
      <div className="ik-guide-card__eyebrow">{guide.eyebrow}</div>
      <h3 className="ik-guide-card__title">
        <a href="#" onClick={(e) => { e.preventDefault(); onOpen(guide); }}>{guide.title}</a>
      </h3>
      <p className="ik-guide-card__description">{guide.description}</p>
      <TagList tags={guide.tags} />
    </article>
  );
}

window.GuideCard = GuideCard;
