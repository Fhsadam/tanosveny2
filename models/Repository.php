<?php
class Repository {
    private array $config;
    private ?PDO $pdo = null;
    private string $jsonFile;
    private ?array $data = null;

    public function __construct(array $config) {
        $this->config = $config;
        $this->jsonFile = dirname(__DIR__) . '/data/demo.json';
        if ($this->config['storage'] === 'mysql') {
            $this->pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    }
    private function isMysql(): bool { return $this->config['storage'] === 'mysql'; }
    private function load(): array {
        if ($this->data === null) {
            $this->data = json_decode(file_get_contents($this->jsonFile), true, 512, JSON_THROW_ON_ERROR);
        }
        return $this->data;
    }
    private function save(): void {
        if ($this->data !== null) file_put_contents($this->jsonFile, json_encode($this->data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }
    private function nextId(array $rows, string $key='id'): int { return empty($rows) ? 1 : max(array_column($rows, $key)) + 1; }

    public function stats(): array {
        if ($this->isMysql()) {
            return [
                'utak'=>(int)$this->pdo->query('SELECT COUNT(*) FROM ut')->fetchColumn(),
                'telepulesek'=>(int)$this->pdo->query('SELECT COUNT(*) FROM telepules')->fetchColumn(),
                'parkok'=>(int)$this->pdo->query('SELECT COUNT(*) FROM np')->fetchColumn(),
                'hossz'=>(float)$this->pdo->query('SELECT SUM(hossz) FROM ut')->fetchColumn(),
            ];
        }
        $d=$this->load(); return ['utak'=>count($d['ut']), 'telepulesek'=>count($d['telepules']), 'parkok'=>count($d['np']), 'hossz'=>array_sum(array_column($d['ut'], 'hossz'))];
    }
    public function listNationalParks(): array { if ($this->isMysql()) return $this->pdo->query('SELECT * FROM np ORDER BY nev')->fetchAll(); $d=$this->load(); $r=$d['np']; usort($r, fn($a,$b)=>strcmp($a['nev'],$b['nev'])); return $r; }
    public function listTelepulesek(): array { if ($this->isMysql()) return $this->pdo->query('SELECT * FROM telepules ORDER BY nev')->fetchAll(); $d=$this->load(); $r=$d['telepules']; usort($r, fn($a,$b)=>strcmp($a['nev'],$b['nev'])); return $r; }

    public function listTrails(string $search='', string $npid='', int $limit=200): array {
        if ($this->isMysql()) {
            $sql = 'SELECT u.*, t.nev AS telepules, n.nev AS nemzeti_park FROM ut u JOIN telepules t ON u.telepulesid=t.id JOIN np n ON t.npid=n.id WHERE 1=1';
            $params=[];
            if ($search !== '') { $sql .= ' AND (u.nev LIKE ? OR t.nev LIKE ? OR n.nev LIKE ?)'; $like='%'.$search.'%'; $params=[$like,$like,$like]; }
            if ($npid !== '') { $sql .= ' AND n.id = ?'; $params[]=$npid; }
            $sql .= ' ORDER BY u.nev LIMIT '.(int)$limit;
            $st=$this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
        }
        $d=$this->load(); $tels=[]; foreach($d['telepules'] as $t) $tels[$t['id']]=$t; $nps=[]; foreach($d['np'] as $n) $nps[$n['id']]=$n;
        $rows=[];
        foreach($d['ut'] as $u) {
            $t=$tels[$u['telepulesid']] ?? ['nev'=>'','npid'=>0]; $n=$nps[$t['npid']] ?? ['nev'=>'','id'=>0];
            $row=$u+['telepules'=>$t['nev'], 'nemzeti_park'=>$n['nev'], 'npid'=>$n['id']];
            $hay=s_lower($row['nev'].' '.$row['telepules'].' '.$row['nemzeti_park']);
            if ($search!=='' && !str_contains($hay, s_lower($search))) continue;
            if ($npid!=='' && (string)$row['npid'] !== (string)$npid) continue;
            $rows[]=$row;
        }
        usort($rows, fn($a,$b)=>strcmp($a['nev'],$b['nev'])); return array_slice($rows,0,$limit);
    }
    public function getTrail(int $id): ?array {
        if ($this->isMysql()) { $st=$this->pdo->prepare('SELECT * FROM ut WHERE azon=?'); $st->execute([$id]); return $st->fetch() ?: null; }
        $d=$this->load(); foreach($d['ut'] as $u) if ((int)$u['azon']===$id) return $u; return null;
    }
    public function createTrail(array $data): int {
        if ($this->isMysql()) { $st=$this->pdo->prepare('INSERT INTO ut (nev,hossz,allomas,ido,vezetes,telepulesid) VALUES (?,?,?,?,?,?)'); $st->execute([$data['nev'],$data['hossz'],$data['allomas'],$data['ido'],$data['vezetes'],$data['telepulesid']]); return (int)$this->pdo->lastInsertId(); }
        $d=$this->load(); $data['azon']=$this->nextId($d['ut'], 'azon'); $this->data['ut'][]=$data; $this->save(); return $data['azon'];
    }
    public function updateTrail(int $id, array $data): void {
        if ($this->isMysql()) { $st=$this->pdo->prepare('UPDATE ut SET nev=?, hossz=?, allomas=?, ido=?, vezetes=?, telepulesid=? WHERE azon=?'); $st->execute([$data['nev'],$data['hossz'],$data['allomas'],$data['ido'],$data['vezetes'],$data['telepulesid'],$id]); return; }
        $d=$this->load(); foreach($this->data['ut'] as &$u) if ((int)$u['azon']===$id) { $u=array_merge($u,$data); break; } $this->save();
    }
    public function deleteTrail(int $id): void {
        if ($this->isMysql()) { $st=$this->pdo->prepare('DELETE FROM ut WHERE azon=?'); $st->execute([$id]); return; }
        $d=$this->load(); $this->data['ut']=array_values(array_filter($this->data['ut'], fn($u)=>(int)$u['azon']!==$id)); $this->save();
    }
    public function findUser(string $login): ?array {
        if ($this->isMysql()) { $st=$this->pdo->prepare('SELECT * FROM users WHERE login=?'); $st->execute([$login]); return $st->fetch() ?: null; }
        $d=$this->load(); foreach($d['users'] as $u) if (s_lower($u['login'])===s_lower($login)) return $u; return null;
    }
    public function createUser(array $data): void {
        if ($this->isMysql()) { $st=$this->pdo->prepare('INSERT INTO users (csaladi_nev, utonev, login, password_hash) VALUES (?,?,?,?)'); $st->execute([$data['csaladi_nev'],$data['utonev'],$data['login'],$data['password_hash']]); return; }
        $d=$this->load(); $data['id']=$this->nextId($d['users']); $data['created_at']=date('Y-m-d H:i:s'); $this->data['users'][]=$data; $this->save();
    }
    public function saveMessage(array $m): int {
        if ($this->isMysql()) { $st=$this->pdo->prepare('INSERT INTO uzenetek (name,email,subject,message,sender_user) VALUES (?,?,?,?,?)'); $st->execute([$m['name'],$m['email'],$m['subject'],$m['message'],$m['sender_user']]); return (int)$this->pdo->lastInsertId(); }
        $d=$this->load(); $m['id']=$this->nextId($d['messages']); $m['created_at']=date('Y-m-d H:i:s'); $this->data['messages'][]=$m; $this->save(); return $m['id'];
    }
    public function getMessage(int $id): ?array { $rows=$this->listMessages(1000); foreach($rows as $r) if((int)$r['id']===$id) return $r; return null; }
    public function listMessages(int $limit=100): array {
        if ($this->isMysql()) return $this->pdo->query('SELECT * FROM uzenetek ORDER BY created_at DESC LIMIT '.(int)$limit)->fetchAll();
        $d=$this->load(); $r=$d['messages']; usort($r, fn($a,$b)=>strcmp($b['created_at'],$a['created_at'])); return array_slice($r,0,$limit);
    }
    public function listImages(): array {
        if ($this->isMysql()) return $this->pdo->query('SELECT * FROM kepek ORDER BY created_at DESC, id DESC')->fetchAll();
        $d=$this->load(); $r=$d['images']; usort($r, fn($a,$b)=>strcmp($b['created_at'],$a['created_at'])); return $r;
    }
    public function saveImage(array $img): void {
        if ($this->isMysql()) { $st=$this->pdo->prepare('INSERT INTO kepek (title,filename,uploaded_by) VALUES (?,?,?)'); $st->execute([$img['title'],$img['filename'],$img['uploaded_by']]); return; }
        $d=$this->load(); $img['id']=$this->nextId($d['images']); $img['created_at']=date('Y-m-d H:i:s'); $this->data['images'][]=$img; $this->save();
    }
}
