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
            foreach ($content['rows'] as $row) {
                if ($row['htype'] == 10) {
                    if (!$first) {
                        print '</li>';
                        $first = 1;
                    } ?>
                        <li>
                            <a <?= isset($row['sitting']) ? 'id="sitting' . $row['sitting'] . '" ' : '' ?>href="<?= $row['listurl'] ?>" class="business-list__title">
                                <h3>
                                    <?= $row['body'] ?>
                                </h3>
                            </a>
                            <p class="business-list__meta">
                                <?php if (isset($row['sitting'])) { ?>
                                    <?= make_ranking($row['sitting']) ?> sitting &middot;
                                <?php } ?>
                                <?= format_date($row['hdate'], LONGERDATEFORMAT) ?>
                                <?php if ($row['contentcount'] > 0) { ?>
                                &middot; <?= $row['contentcount'] == 1 ? '1 speech' : $row['contentcount'] . ' speeches' ?>
                                <?php } ?>
                            </p>
                            <p class="business-list__excerpt">
                                <?= trim_characters($row['excerpt'], 0, 200) ?>
                            </p>
                    <?php } else { ?>
                    <ul>
                        <li>
                            <a href="<?= $row['listurl'] ?>" class="business-list__title">
                                <h3>
                                    <?= $row['body'] ?>
                                </h3>
                            </a>
                            <?php if (isset($row['contentcount'])) { ?>
                                <p class="business-list__meta">
                                    <?= $row['contentcount'] == 1 ? '1 speech' : $row['contentcount'] . ' speeches' ?>
                                </p>
                            <?php } ?>
                            <?php if (isset($row['excerpt'])) { ?>
                                <p class="business-list__excerpt">
                                    <?= trim_characters($row['excerpt'], 0, 200) ?>
                                </p>
                            <?php } ?>
                        </li>
                    </ul>
                <?php }
                    } ?>
            </ul>
        </div>

        <?php if (isset($content['info']['committee'])) {
            $committee = $content['info']['committee'];
            ?>
        <div class="business-section__secondary">
            <p>
            <a href="/pbc/<?= $session ?>/">All bills in this session</a>
            </p>

            <p><strong>Committee membership and attendance (out of <?= $committee['sittings'] ?>)</strong></p>

            <p><strong>Chairpersons</strong></p>
            <ul>
            <?php foreach($committee['chairmen'] as $chair) { ?>
                <li><?= $chair['name'] ?> (<?= $chair['attending'] ?>)</li>
            <?php } ?>
            </ul>
            <p><strong>Members</strong></p>
            <ul>
            <?php foreach($committee['members'] as $chair) { ?>
                <li><?= $chair['name'] ?> (<?= $chair['attending'] ?>)</li>
            <?php } ?>
            </ul>
            <p>
            <small>[ Committee memberships can change partway through ]</small>
            </p>
        </div>
        <?php } ?>
    </div>


</div>
