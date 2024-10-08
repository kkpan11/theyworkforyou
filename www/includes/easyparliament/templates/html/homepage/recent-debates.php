                            <?php if (isset($recent['child'])) { ?>
                            <li class="parliamentary-excerpt">
                            <h3 class="excerpt__title"><a href="<?= $recent['list_url'] ?>"><?= $recent['parent']['body'] ? $recent['parent']['body'] . ' : ' : '' ?><?= $recent['body'] ?></a></h3>
                                <p class="meta"><?=format_date($recent['hdate'], LONGERDATEFORMAT); ?></p>
                                <p class="meta excerpt__category"><a href="<?= $recent['more_url'] ?>"><?= $recent['desc'] ?></a></p>
                                <p class="excerpt__statement">
                                <?php if (isset($recent['child']['speaker']) && count($recent['child']['speaker'])) { ?>
                                <a href="<?= $recent['child']['speaker']['url'] ?>"><?= $recent['child']['speaker']['name'] ?></a>
                                <?php } ?>
                                <?= trim_characters($recent['child']['body'], 0, 200) ?>
                                </p>
                            </li>
                            <?php } else { ?>
                            <li class="parliamentary-excerpt">
                            <h3 class="excerpt__title"><?= $recent['desc'] ?></h3>
                                <?php foreach ($recent['data'] as $date => $details) { ?>
                                <p class="meta"><?= $date ?></p>
                                <p class="meta excerpt__category"><a href="<?= $recent['more_url'] ?>"><?= $recent['desc'] ?></a></p>
                                <?php foreach ($details as $bill) { ?>
                                <p class="excerpt__statement">
                                    <a href="<?= $bill['url'] ?>"><?= $bill['bill'] ?>, <?= $bill['sitting'] ?></a>
                                </p>
                                <?php } ?>
                                <?php } ?>
                            </li>
                            <?php } ?>
