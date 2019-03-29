workflow "Deploy on Forge" {
  on = "push"
  resolves = ["Trigger Forge Deployment"]
}

action "Filter for master" {
  uses = "actions/bin/filter@master"
  args = "branch master"
}

action "Trigger Forge Deployment" {
  uses = "swinton/httpie.action@master"
  needs = ["Filter for master"]
  args = ["GET", "https://forge.laravel.com/servers/156510/sites/691686/deploy/http?token=WpKhsMJVxdBhRJC8WlgMTs0ngv2DlCzln2URhnca"]
}
