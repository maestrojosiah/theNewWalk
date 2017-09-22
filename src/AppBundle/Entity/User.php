<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Mgilet\NotificationBundle\Model\UserNotificationInterface;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})}, indexes={@ORM\Index(name="first_name", columns={"first_name", "last_name", "email"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */

class User implements UserNotificationInterface, AdvancedUserInterface, \Serializable
{
    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=20, nullable=false)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=20, nullable=false)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=false)
     */
    private $email;

    /**
     * 
     * @Assert\Length(max=4096)
     * @Assert\Length(min=4)
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     */
    private $randomAuth;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=100, nullable=false)
     */
    private $gender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_admin", type="boolean", nullable=false)
     */
    private $isAdmin;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="integer", nullable=false)
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Notification
     * @ORM\OneToMany(targetEntity="Notification", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    protected $notifications;

    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="user")
     */
    private $posts;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="user")
     */
    private $groups;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="user")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="CommentR", mappedBy="user")
     */
    private $commentRs;

    /**
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="user")
     */
    private $photos;

    /**
     * @ORM\OneToMany(targetEntity="Discussion", mappedBy="user")
     */
    private $discussions;

    /**
     * @ORM\OneToMany(targetEntity="ArticleL", mappedBy="user")
     */
    private $likes;

    /**
     * @ORM\OneToMany(targetEntity="ArticleM", mappedBy="user")
     */
    private $articlesM;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="user")
     */
    private $articles;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->commentRs = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->discussions = new ArrayCollection();
        $this->articlesM = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->isAdmin = false;
        $this->active = true;
    }    

    public function __toString() {
        return $this->email;
    }    

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return User
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     *
     * @return User
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add post
     *
     * @param \AppBundle\Entity\Post $post
     *
     * @return User
     */
    public function addPost(\AppBundle\Entity\Post $post)
    {
        $this->posts[] = $post;

        return $this;
    }

    /**
     * Remove post
     *
     * @param \AppBundle\Entity\Post $post
     */
    public function removePost(\AppBundle\Entity\Post $post)
    {
        $this->posts->removeElement($post);
    }

    /**
     * Get posts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * Add group
     *
     * @param \AppBundle\Entity\Group $group
     *
     * @return User
     */
    public function addGroup(\AppBundle\Entity\Group $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group
     *
     * @param \AppBundle\Entity\Group $group
     */
    public function removeGroup(\AppBundle\Entity\Group $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return User
     */
    public function addComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \AppBundle\Entity\Comment $comment
     */
    public function removeComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add commentR
     *
     * @param \AppBundle\Entity\CommentR $commentR
     *
     * @return User
     */
    public function addCommentR(\AppBundle\Entity\CommentR $commentR)
    {
        $this->commentRs[] = $commentR;

        return $this;
    }

    /**
     * Remove commentR
     *
     * @param \AppBundle\Entity\CommentR $commentR
     */
    public function removeCommentR(\AppBundle\Entity\CommentR $commentR)
    {
        $this->commentRs->removeElement($commentR);
    }

    /**
     * Get commentRs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCommentRs()
    {
        return $this->commentRs;
    }

    /**
     * Add photo
     *
     * @param \AppBundle\Entity\Photo $photo
     *
     * @return User
     */
    public function addPhoto(\AppBundle\Entity\Photo $photo)
    {
        $this->photos[] = $photo;

        return $this;
    }

    /**
     * Remove photo
     *
     * @param \AppBundle\Entity\Photo $photo
     */
    public function removePhoto(\AppBundle\Entity\Photo $photo)
    {
        $this->photos->removeElement($photo);
    }

    /**
     * Get photos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * Add photoC
     *
     * @param \AppBundle\Entity\PhotoC $photoC
     *
     * @return User
     */
    public function addPhotoC(\AppBundle\Entity\PhotoC $photoC)
    {
        $this->photoCs[] = $photoC;

        return $this;
    }

    /**
     * Remove photoC
     *
     * @param \AppBundle\Entity\PhotoC $photoC
     */
    public function removePhotoC(\AppBundle\Entity\PhotoC $photoC)
    {
        $this->photoCs->removeElement($photoC);
    }

    /**
     * Get photoCs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhotoCs()
    {
        return $this->photoCs;
    }

    /**
     * Add discussion
     *
     * @param \AppBundle\Entity\Discussion $discussion
     *
     * @return User
     */
    public function addDiscussion(\AppBundle\Entity\Discussion $discussion)
    {
        $this->discussions[] = $discussion;

        return $this;
    }

    /**
     * Remove discussion
     *
     * @param \AppBundle\Entity\Discussion $discussion
     */
    public function removeDiscussion(\AppBundle\Entity\Discussion $discussion)
    {
        $this->discussions->removeElement($discussion);
    }

    /**
     * Get discussions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDiscussions()
    {
        return $this->discussions;
    }

    /**
     * Add articlesM
     *
     * @param \AppBundle\Entity\ArticleM $articlesM
     *
     * @return User
     */
    public function addArticlesM(\AppBundle\Entity\ArticleM $articlesM)
    {
        $this->articlesM[] = $articlesM;

        return $this;
    }

    /**
     * Remove articlesM
     *
     * @param \AppBundle\Entity\ArticleM $articlesM
     */
    public function removeArticlesM(\AppBundle\Entity\ArticleM $articlesM)
    {
        $this->articlesM->removeElement($articlesM);
    }

    /**
     * Get articlesM
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArticlesM()
    {
        return $this->articlesM;
    }

    /**
     * Add article
     *
     * @param \AppBundle\Entity\Article $article
     *
     * @return User
     */
    public function addArticle(\AppBundle\Entity\Article $article)
    {
        $this->articles[] = $article;

        return $this;
    }

    /**
     * Remove article
     *
     * @param \AppBundle\Entity\Article $article
     */
    public function removeArticle(\AppBundle\Entity\Article $article)
    {
        $this->articles->removeElement($article);
    }

    public function getUsername()
    {
        return $this->email;
    }
    /**
     * Get articles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArticles()
    {
        return $this->articles;
    }
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->password,
            $this->active
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            $this->active
        ) = unserialize($serialized);
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->active;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getRoles()
    {
        return array('ROLE_ADMIN');
    }

    public function eraseCredentials()
    {
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }

    /**
     * Set active
     *
     * @param integer $active
     *
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return integer
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set randomAuth
     *
     * @param string $randomAuth
     *
     * @return User
     */
    public function setRandomAuth($randomAuth)
    {
        $this->randomAuth = $randomAuth;

        return $this;
    }

    /**
     * Get randomAuth
     *
     * @return string
     */
    public function getRandomAuth()
    {
        return $this->randomAuth;
    }

    /**
     * Add like
     *
     * @param \AppBundle\Entity\ArticleL $like
     *
     * @return User
     */
    public function addLike(\AppBundle\Entity\ArticleL $like)
    {
        $this->likes[] = $like;

        return $this;
    }

    /**
     * Remove like
     *
     * @param \AppBundle\Entity\ArticleL $like
     */
    public function removeLike(\AppBundle\Entity\ArticleL $like)
    {
        $this->likes->removeElement($like);
    }

    /**
     * Get likes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * {@inheritdoc}
     */
    public function addNotification($notification)
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeNotification($notification)
    {
        if ($this->notifications->contains($notification)) {
            $this->notifications->removeElement($notification);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        $this->getId();
    }
        
}
