DEV_PROTOCOL = "http"
DEV_HOST = "192.168.50.10"
DEV_PARSOID = "192.168.50.11"
PROD_PROTOCOL = "https"
PROD_HOST = "femiwiki.com"
PROD_PARSOID = "parsoid.femiwiki.com"
PROD_WWW_TAG = "www"
PROD_PARSOID_TAG = "parsoid"

Vagrant.configure("2") do |config|
  config.vm.define "dev-www" do |c|
    c.vm.box = "ubuntu/trusty64"
    c.vm.provision "shell", path: "./www/provision.sh", args: "#{DEV_PROTOCOL} #{DEV_HOST} #{DEV_PARSOID} #{ENV['ADMIN_PW']}"
    c.vm.network "private_network", ip: "192.168.50.10"
    c.vm.provider :virtualbox do |v|
      v.memory = 1024
      v.cpus = 1
    end
  end

  config.vm.define "prod-www" do |c|
    c.vm.box = "awsdummy"
    c.vm.provision "shell", path: "./www/provision.sh", args: "#{PROD_PROTOCOL} #{PROD_HOST} #{PROD_PARSOID} #{ENV['ADMIN_PW']}"
    c.vm.provider :aws do |aws, override|
      aws.aws_dir = "./.aws/"
      aws.keypair_name = "fw"
      aws.ami = "ami-09dc1267"
      aws.instance_type = "t2.micro"
      aws.security_groups = ["www"]
      aws.tags = {
        'Name' => PROD_WWW_TAG
      }
      aws.block_device_mapping = [{ 'DeviceName' => '/dev/sda1', 'Ebs.VolumeSize' => 16 }]
      override.ssh.username = "ubuntu"
      override.ssh.private_key_path = "fw.pem"
    end
  end
end
