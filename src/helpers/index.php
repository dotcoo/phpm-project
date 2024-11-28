<?php
function setting_get(string $name, mixed $defval = null) : mixed {
  return \app\models\SystemSetting::whereByKey($name)->select()?->content ?? $defval;
}
