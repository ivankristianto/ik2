-- Grants for the corpus worker's MySQL user, as in the embedding corpus spec.
-- Table names are unqualified so the grants land in whichever database this
-- runs against. Local only: production uses a generated password.
-- Safe to repeat. Run through docker/corpus/grant.sh, after the ik2 plugin has
-- created the tables (GRANT on a missing table fails).

CREATE USER IF NOT EXISTS 'ik2_corpus'@'%' IDENTIFIED BY 'ik2_corpus_local';
ALTER USER 'ik2_corpus'@'%' IDENTIFIED BY 'ik2_corpus_local';

-- Start from nothing so a grant dropped from this file is dropped locally too.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'ik2_corpus'@'%';

GRANT SELECT, UPDATE, DELETE ON wp_ik2_corpus_documents TO 'ik2_corpus'@'%';
GRANT SELECT, DELETE ON wp_ik2_corpus_chunks TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON wp_ik2_corpus_vectors TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, DELETE ON wp_ik2_corpus_related TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT ON wp_ik2_corpus_runs TO 'ik2_corpus'@'%';
