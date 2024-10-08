<div class="full-page__row">

    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
                Public Bill Committees
            </h1>
            <div class="business-section__header__description">
                <p>
                Previously called Standing Committees, Public Bill Commitees
                study proposed legislation (Bills) in detail, debating each
                clause and reporting any amendments to the Commons for further debate.
                </p>

                <p>
                There are at least 16 MPs on a Committee, and the proportion
                of parties reflects the House of Commons, so the government
                always has a majority.
                </p>
            </div>
        </div>

        <div class="business-section__primary">
            <ul class="business-list">
            <?php
                $first = 0;
            foreach ($rows as $row) { ?>
                    <li>
                        <a href="<?= $row['url'] ?>" class="business-list__title">
                            <h3>
                                <?= $row['title'] ?>
                            </h3>
                        </a>
                        <?php if (isset($row['contentcount'])) { ?>
                            <p class="business-list__meta">
                                <?= $row['contentcount'] == 1 ? '1 speech' : $row['contentcount'] . ' speeches' ?>
                            </p>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="business-section__secondary">
            <div class="calendar__controls">
                <?php if (isset($prev)) { ?>
                <a href="<?= $prev['url'] ?>" class="calendar__controls__previous">&larr;</a>
                <?php } else { ?>
                <span class="calendar__controls__previous">&nbsp;</span>
                <?php } ?>
                <span class="calendar__controls__current">
                    <?= $session ?> session
                </span>
                <?php if (isset($next)) { ?>
                <a href="<?= $next['url'] ?>" class="calendar__controls__next">&rarr;</a>
                <?php } else { ?>
                <span class="calendar__controls__next">&nbsp;</span>
                <?php } ?>
            </div>
        </div>
    </div>


</div>
