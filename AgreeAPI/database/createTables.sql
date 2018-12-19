CREATE TABLE users (
    id varchar(40) UNIQUE NOT NULL,
    email varchar(64) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    token varchar(40) NULL,
    lastActivity DateTime NULL,
    activityProfile text NULL,
    PRIMARY KEY (id)
);

CREATE TABLE questions (
    id varchar(40) UNIQUE NOT NULL,
    title varchar(240) NOT NULL,
    userId varchar(40) NOT NULL,
    startTime DateTime NULL,
    hardDeadline DateTime NULL,
    movingDeadlineSeconds int(4) NULL,
    trashed int(1),
    PRIMARY KEY (id),
    FOREIGN KEY (userId)
        REFERENCES users(id)
            ON DELETE CASCADE
);

CREATE TABLE choices (
    id varchar(40) UNIQUE NOT NULL,
    questionId varchar(40) NOT NULL,
    title varchar(240),
    trashed int(1),
    PRIMARY KEY (id),
    FOREIGN KEY (questionId)
        REFERENCES questions(id)
            ON DELETE CASCADE
);

CREATE TABLE invitations (
    id varchar(40) UNIQUE NOT NULL,
    questionId varchar(40) NOT NULL,
    email varchar(64) NULL,
	used bit (1),
    PRIMARY KEY (id),
    FOREIGN KEY (questionId)
        REFERENCES questions(id)
            ON DELETE CASCADE
);

CREATE TABLE ballots (
    id varchar(40) UNIQUE NOT NULL,
    questionId varchar(40) NOT NULL,
    rejected int(3) NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (questionId)
        REFERENCES questions(id)
            ON DELETE CASCADE
);

CREATE TABLE votes (
    id int(11) UNIQUE AUTO_INCREMENT,
    ballotId varchar(40) NOT NULL,
    choiceId varchar(40) NOT NULL,
    preference int(2) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (ballotId)
        REFERENCES ballots(id)
            ON DELETE CASCADE,
    FOREIGN KEY (choiceId)
        REFERENCES choices(id)
            ON DELETE CASCADE,
    UNIQUE(ballotId,choiceId,preference)
);

CREATE TABLE decisions (
    id int(11) UNIQUE AUTO_INCREMENT,
    questionId varchar(40) NOT NULL,
    choiceId varchar(40) NULL,
    narrative varchar(255),
    endTime DateTime NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (questionId)
        REFERENCES questions (id)
            ON DELETE CASCADE,
    FOREIGN KEY (choiceId)
        REFERENCES choices (id)
            ON DELETE CASCADE,
    UNIQUE (questionId)
);


CREATE TABLE logs (
    id int(11) UNIQUE AUTO_INCREMENT,
    ipaddr varchar(15),
	requestTime varchar(32),
    narrative varchar(255),
    PRIMARY KEY (id)
);